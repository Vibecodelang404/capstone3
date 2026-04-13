import { NextRequest, NextResponse } from 'next/server';

// Mock inventory data
const mockInventory = [
  {
    id: '1',
    product_id: '1',
    quantity: 50,
    reorder_level: 10,
    reorder_quantity: 25,
    last_updated: new Date().toISOString(),
  },
  {
    id: '2',
    product_id: '2',
    quantity: 5,
    reorder_level: 20,
    reorder_quantity: 50,
    last_updated: new Date().toISOString(),
  },
];

export async function GET(request: NextRequest) {
  return NextResponse.json(mockInventory);
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();

    const newItem = {
      id: Date.now().toString(),
      last_updated: new Date().toISOString(),
      ...body,
    };

    return NextResponse.json(newItem, { status: 201 });
  } catch (error) {
    return NextResponse.json(
      { error: 'Failed to create inventory item' },
      { status: 400 }
    );
  }
}
