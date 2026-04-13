import { NextRequest, NextResponse } from 'next/server';

// Mock orders data
const mockOrders = [
  {
    id: '1',
    order_date: new Date().toISOString(),
    supplier_id: '1',
    status: 'PENDING',
    total_amount: 5000,
    expected_delivery_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString(),
    notes: '',
  },
];

export async function GET(request: NextRequest) {
  const searchParams = request.nextUrl.searchParams;
  const status = searchParams.get('status');

  let orders = mockOrders;

  if (status) {
    orders = orders.filter((o) => o.status === status);
  }

  return NextResponse.json(orders);
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();

    const newOrder = {
      id: Date.now().toString(),
      order_date: new Date().toISOString(),
      ...body,
    };

    return NextResponse.json(newOrder, { status: 201 });
  } catch (error) {
    return NextResponse.json(
      { error: 'Failed to create order' },
      { status: 400 }
    );
  }
}
