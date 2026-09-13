from alembic import op
import sqlalchemy as sa


revision = "008_returns"
down_revision = "007_sale_customer"
branch_labels = None
depends_on = None


def upgrade():
    op.create_table(
        "returns",
        sa.Column(
            "id",
            sa.Integer(),
            primary_key=True,
        ),
        sa.Column(
            "sale_id",
            sa.Integer(),
            nullable=False,
        ),
        sa.Column(
            "store_id",
            sa.Integer(),
            nullable=False,
        ),
        sa.Column(
            "user_id",
            sa.Integer(),
            nullable=False,
        ),
        sa.Column(
            "refund_amount",
            sa.Numeric(12, 2),
            nullable=False,
        ),
        sa.Column(
            "refund_method",
            sa.String(20),
            nullable=False,
        ),
        sa.Column(
            "reason",
            sa.String(255),
            nullable=True,
        ),
        sa.Column(
            "status",
            sa.String(30),
            nullable=False,
            server_default="COMPLETED",
        ),
        sa.Column(
            "created_at",
            sa.DateTime(),
            nullable=False,
        ),
        sa.ForeignKeyConstraint(
            ["sale_id"],
            ["sales.id"],
        ),
        sa.ForeignKeyConstraint(
            ["store_id"],
            ["stores.id"],
        ),
        sa.ForeignKeyConstraint(
            ["user_id"],
            ["users.id"],
        ),
    )

    op.create_index(
        "ix_returns_sale_id",
        "returns",
        ["sale_id"],
    )

    op.create_index(
        "ix_returns_store_id",
        "returns",
        ["store_id"],
    )

    op.create_table(
        "return_items",
        sa.Column(
            "id",
            sa.Integer(),
            primary_key=True,
        ),
        sa.Column(
            "return_id",
            sa.Integer(),
            nullable=False,
        ),
        sa.Column(
            "sale_item_id",
            sa.Integer(),
            nullable=False,
        ),
        sa.Column(
            "product_id",
            sa.Integer(),
            nullable=False,
        ),
        sa.Column(
            "quantity",
            sa.Integer(),
            nullable=False,
        ),
        sa.Column(
            "refund_amount",
            sa.Numeric(12, 2),
            nullable=False,
        ),
        sa.ForeignKeyConstraint(
            ["return_id"],
            ["returns.id"],
        ),
        sa.ForeignKeyConstraint(
            ["sale_item_id"],
            ["sale_items.id"],
        ),
        sa.ForeignKeyConstraint(
            ["product_id"],
            ["products.id"],
        ),
    )


def downgrade():
    op.drop_table("return_items")

    op.drop_index(
        "ix_returns_store_id",
        table_name="returns",
    )

    op.drop_index(
        "ix_returns_sale_id",
        table_name="returns",
    )

    op.drop_table("returns")
