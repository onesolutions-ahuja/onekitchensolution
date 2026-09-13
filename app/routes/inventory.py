from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from app.database import get_db
from app.models.inventory import Inventory
from app.models.product import Product
from app.models.store import Store
from app.security.dependencies import get_current_user


router = APIRouter(
    prefix="/inventory",
    tags=["Inventory"],
)


def is_owner(user):
    return getattr(user, "role_id", None) == 1


def check_store_access(db, user, store_id):
    if is_owner(user):
        return True

    from app.models.user_store_access import UserStoreAccess

    access = db.query(UserStoreAccess).filter(
        UserStoreAccess.user_id == user.id,
        UserStoreAccess.store_id == store_id,
    ).first()

    return access is not None


@router.get("/")
def get_inventory(
    store_id: int | None = None,
    db: Session = Depends(get_db),
    current_user=Depends(get_current_user),
):
    query = db.query(Inventory)

    if store_id is not None:
        if not check_store_access(db, current_user, store_id):
            raise HTTPException(
                status_code=403,
                detail="You do not have access to this store",
            )

        query = query.filter(
            Inventory.store_id == store_id
        )

    elif not is_owner(current_user):
        from app.models.user_store_access import UserStoreAccess

        store_ids = [
            row[0]
            for row in db.query(
                UserStoreAccess.store_id
            ).filter(
                UserStoreAccess.user_id == current_user.id
            ).all()
        ]

        if not store_ids:
            return []

        query = query.filter(
            Inventory.store_id.in_(store_ids)
        )

    return query.order_by(
        Inventory.product_id
    ).all()


@router.get("/{inventory_id}")
def get_inventory_item(
    inventory_id: int,
    db: Session = Depends(get_db),
    current_user=Depends(get_current_user),
):
    inventory = db.query(Inventory).filter(
        Inventory.id == inventory_id
    ).first()

    if not inventory:
        raise HTTPException(
            status_code=404,
            detail="Inventory record not found",
        )

    if not check_store_access(
        db,
        current_user,
        inventory.store_id,
    ):
        raise HTTPException(
            status_code=403,
            detail="You do not have access to this inventory",
        )

    return inventory


@router.post("/")
def create_inventory(
    store_id: int,
    product_id: int,
    quantity: int = 0,
    minimum_quantity: int = 0,
    db: Session = Depends(get_db),
    current_user=Depends(get_current_user),
):
    if not check_store_access(db, current_user, store_id):
        raise HTTPException(
            status_code=403,
            detail="You do not have access to this store",
        )

    store = db.query(Store).filter(
        Store.id == store_id
    ).first()

    if not store:
        raise HTTPException(
            status_code=404,
            detail="Store not found",
        )

    product = db.query(Product).filter(
        Product.id == product_id
    ).first()

    if not product:
        raise HTTPException(
            status_code=404,
            detail="Product not found",
        )

    existing = db.query(Inventory).filter(
        Inventory.store_id == store_id,
        Inventory.product_id == product_id,
    ).first()

    if existing:
        raise HTTPException(
            status_code=400,
            detail="Inventory record already exists",
        )

    inventory = Inventory(
        store_id=store_id,
        product_id=product_id,
        quantity=quantity,
        minimum_quantity=minimum_quantity,
        cost_price=product.cost_price,
        selling_price=product.selling_price,
    )

    db.add(inventory)
    db.commit()
    db.refresh(inventory)

    return inventory


@router.put("/{inventory_id}")
def update_inventory(
    inventory_id: int,
    quantity: int | None = None,
    minimum_quantity: int | None = None,
    db: Session = Depends(get_db),
    current_user=Depends(get_current_user),
):
    inventory = db.query(Inventory).filter(
        Inventory.id == inventory_id
    ).first()

    if not inventory:
        raise HTTPException(
            status_code=404,
            detail="Inventory record not found",
        )

    if not check_store_access(
        db,
        current_user,
        inventory.store_id,
    ):
        raise HTTPException(
            status_code=403,
            detail="You do not have access to this inventory",
        )

    if quantity is not None:
        if quantity < 0:
            raise HTTPException(
                status_code=400,
                detail="Quantity cannot be negative",
            )
        inventory.quantity = quantity

    if minimum_quantity is not None:
        if minimum_quantity < 0:
            raise HTTPException(
                status_code=400,
                detail="Minimum quantity cannot be negative",
            )
        inventory.minimum_quantity = minimum_quantity

    db.commit()
    db.refresh(inventory)

    return inventory


@router.get("/store/{store_id}/low-stock")
def get_low_stock(
    store_id: int,
    db: Session = Depends(get_db),
    current_user=Depends(get_current_user),
):
    if not check_store_access(db, current_user, store_id):
        raise HTTPException(
            status_code=403,
            detail="You do not have access to this store",
        )

    return db.query(Inventory).filter(
        Inventory.store_id == store_id,
        Inventory.quantity <= Inventory.minimum_quantity,
    ).order_by(
        Inventory.quantity.asc()
    ).all()
