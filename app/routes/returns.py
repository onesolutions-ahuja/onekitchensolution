from decimal import Decimal

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from app.database import get_db
from app.models.inventory import Inventory
from app.models.return_ import Return, ReturnItem
from app.models.sale import Sale, SaleItem
from app.models.user_store_access import UserStoreAccess
from app.schemas.return_ import ReturnCreate
from app.security.dependencies import get_current_user


router = APIRouter(
    prefix="/returns",
    tags=["Returns"],
)


def is_owner(user):
    return getattr(user, "role_id", None) == 1


def check_store_access(db, user, store_id):
    if is_owner(user):
        return True

    access = (
        db.query(UserStoreAccess)
        .filter(
            UserStoreAccess.user_id == user.id,
            UserStoreAccess.store_id == store_id,
        )
        .first()
    )

    return access is not None


@router.post("/")
def create_return(
    data: ReturnCreate,
    db: Session = Depends(get_db),
    current_user=Depends(get_current_user),
):
    sale = (
        db.query(Sale)
        .filter(Sale.id == data.sale_id)
        .first()
    )

    if not sale:
        raise HTTPException(
            status_code=404,
            detail="Sale not found",
        )

    if not check_store_access(
        db,
        current_user,
        sale.store_id,
    ):
        raise HTTPException(
            status_code=403,
            detail="You do not have access to this store",
        )

    if sale.status == "VOID":
        raise HTTPException(
            status_code=400,
            detail="Voided sales cannot be returned",
        )

    if sale.status != "COMPLETED":
        raise HTTPException(
            status_code=400,
            detail="Only completed sales can be returned",
        )

    if not data.items:
        raise HTTPException(
            status_code=400,
            detail="Return must contain at least one item",
        )

    requested_ids = [
        item.sale_item_id
        for item in data.items
    ]

    if len(requested_ids) != len(set(requested_ids)):
        raise HTTPException(
            status_code=400,
            detail="Duplicate sale items are not allowed",
        )

    sale_items = (
        db.query(SaleItem)
        .filter(
            SaleItem.sale_id == sale.id,
            SaleItem.id.in_(requested_ids),
        )
        .all()
    )

    sale_item_map = {
        item.id: item
        for item in sale_items
    }

    if len(sale_item_map) != len(requested_ids):
        raise HTTPException(
            status_code=400,
            detail="One or more sale items do not belong to this sale",
        )

    existing_returns = (
        db.query(ReturnItem)
        .join(
            Return,
            Return.id == ReturnItem.return_id,
        )
        .filter(
            Return.sale_id == sale.id,
            Return.status == "COMPLETED",
            ReturnItem.sale_item_id.in_(requested_ids),
        )
        .all()
    )

    already_returned = {}

    for existing in existing_returns:
        already_returned[existing.sale_item_id] = (
            already_returned.get(
                existing.sale_item_id,
                0,
            )
            + existing.quantity
        )

    refund_total = Decimal("0")
    prepared_items = []

    for request_item in data.items:
        sale_item = sale_item_map[
            request_item.sale_item_id
        ]

        returned_quantity = already_returned.get(
            sale_item.id,
            0,
        )

        remaining_quantity = (
            sale_item.quantity
            - returned_quantity
        )

        if request_item.quantity > remaining_quantity:
            raise HTTPException(
                status_code=400,
                detail=(
                    f"Cannot return "
                    f"{request_item.quantity} of sale item "
                    f"{sale_item.id}. "
                    f"Remaining quantity is "
                    f"{remaining_quantity}."
                ),
            )

        unit_refund = (
            sale_item.line_total
            / sale_item.quantity
        )

        item_refund = (
            unit_refund
            * request_item.quantity
        )

        refund_total += item_refund

        prepared_items.append(
            {
                "sale_item": sale_item,
                "quantity": request_item.quantity,
                "refund_amount": item_refund,
            }
        )

    if refund_total > sale.total:
        raise HTTPException(
            status_code=400,
            detail="Refund amount cannot exceed sale total",
        )

    return_record = Return(
        sale_id=sale.id,
        store_id=sale.store_id,
        user_id=current_user.id,
        refund_amount=refund_total,
        refund_method=data.refund_method.upper(),
        reason=data.reason,
        status="COMPLETED",
    )

    db.add(return_record)
    db.flush()

    for prepared in prepared_items:
        sale_item = prepared["sale_item"]

        return_item = ReturnItem(
            return_id=return_record.id,
            sale_item_id=sale_item.id,
            product_id=sale_item.product_id,
            quantity=prepared["quantity"],
            refund_amount=prepared["refund_amount"],
        )

        db.add(return_item)

        inventory = (
            db.query(Inventory)
            .filter(
                Inventory.store_id == sale.store_id,
                Inventory.product_id == sale_item.product_id,
            )
            .first()
        )

        if inventory:
            inventory.quantity += prepared["quantity"]
        else:
            inventory = Inventory(
                store_id=sale.store_id,
                product_id=sale_item.product_id,
                quantity=prepared["quantity"],
            )
            db.add(inventory)

    db.commit()
    db.refresh(return_record)

    return {
        "message": "Return completed successfully",
        "return_id": return_record.id,
        "sale_id": return_record.sale_id,
        "refund_amount": return_record.refund_amount,
        "refund_method": return_record.refund_method,
        "status": return_record.status,
    }


@router.get("/")
def get_returns(
    store_id: int | None = None,
    db: Session = Depends(get_db),
    current_user=Depends(get_current_user),
):
    query = db.query(Return)

    if is_owner(current_user):
        if store_id is not None:
            query = query.filter(
                Return.store_id == store_id
            )

    else:
        store_ids = [
            row[0]
            for row in (
                db.query(UserStoreAccess.store_id)
                .filter(
                    UserStoreAccess.user_id
                    == current_user.id
                )
                .all()
            )
        ]

        if not store_ids:
            return []

        query = query.filter(
            Return.store_id.in_(store_ids)
        )

        if (
            store_id is not None
            and store_id not in store_ids
        ):
            raise HTTPException(
                status_code=403,
                detail="You do not have access to this store",
            )

    returns = (
        query
        .order_by(Return.created_at.desc())
        .all()
    )

    return [
        {
            "id": item.id,
            "sale_id": item.sale_id,
            "store_id": item.store_id,
            "user_id": item.user_id,
            "refund_amount": item.refund_amount,
            "refund_method": item.refund_method,
            "reason": item.reason,
            "status": item.status,
            "created_at": item.created_at,
        }
        for item in returns
    ]


@router.get("/{return_id}")
def get_return(
    return_id: int,
    db: Session = Depends(get_db),
    current_user=Depends(get_current_user),
):
    return_record = (
        db.query(Return)
        .filter(Return.id == return_id)
        .first()
    )

    if not return_record:
        raise HTTPException(
            status_code=404,
            detail="Return not found",
        )

    if not check_store_access(
        db,
        current_user,
        return_record.store_id,
    ):
        raise HTTPException(
            status_code=403,
            detail="You do not have access to this return",
        )

    items = (
        db.query(ReturnItem)
        .filter(
            ReturnItem.return_id
            == return_record.id
        )
        .all()
    )

    return {
        "id": return_record.id,
        "sale_id": return_record.sale_id,
        "store_id": return_record.store_id,
        "user_id": return_record.user_id,
        "refund_amount": return_record.refund_amount,
        "refund_method": return_record.refund_method,
        "reason": return_record.reason,
        "status": return_record.status,
        "created_at": return_record.created_at,
        "items": [
            {
                "id": item.id,
                "sale_item_id": item.sale_item_id,
                "product_id": item.product_id,
                "quantity": item.quantity,
                "refund_amount": item.refund_amount,
            }
            for item in items
        ],
    }
