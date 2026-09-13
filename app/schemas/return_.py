from datetime import datetime
from decimal import Decimal

from pydantic import BaseModel, Field


class ReturnItemCreate(BaseModel):
    sale_item_id: int
    quantity: int = Field(gt=0)


class ReturnCreate(BaseModel):
    sale_id: int
    refund_method: str = Field(
        min_length=1,
        max_length=20,
    )
    reason: str | None = Field(
        default=None,
        max_length=255,
    )
    items: list[ReturnItemCreate] = Field(
        min_length=1,
    )


class ReturnItemResponse(BaseModel):
    id: int
    sale_item_id: int
    product_id: int
    quantity: int
    refund_amount: Decimal


class ReturnResponse(BaseModel):
    id: int
    sale_id: int
    store_id: int
    user_id: int
    refund_amount: Decimal
    refund_method: str
    reason: str | None
    status: str
    created_at: datetime
    items: list[ReturnItemResponse]
