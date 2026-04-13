import { NextRequest, NextResponse } from 'next/server';

// Mock data for transactions
const mockTransactions = [
  {
    id: '1',
    transaction_date: new Date().toISOString(),
    transaction_type: 'SALE',
    total_amount: 1029.98,
    payment_method: 'CASH',
    status: 'COMPLETED',
    notes: '',
    user_id: '1',
  },
];

export async function GET(request: NextRequest) {
  const searchParams = request.nextUrl.searchParams;
  const date = searchParams.get('date');
  const status = searchParams.get('status');

  let transactions = mockTransactions;

  if (date) {
    transactions = transactions.filter((t) =>
      t.transaction_date.startsWith(date)
    );
  }

  if (status) {
    transactions = transactions.filter((t) => t.status === status);
  }

  return NextResponse.json(transactions);
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();

    // TODO: Validate and save to database via PHP backend
    const newTransaction = {
      id: Date.now().toString(),
      transaction_date: new Date().toISOString(),
      ...body,
    };

    return NextResponse.json(newTransaction, { status: 201 });
  } catch (error) {
    return NextResponse.json(
      { error: 'Failed to create transaction' },
      { status: 400 }
    );
  }
}
