import { NextRequest, NextResponse } from 'next/server';

// Mock data for products
const mockProducts = [
  {
    id: '1',
    name: 'Laptop',
    sku: 'LPT-001',
    category_id: '1',
    price: 999.99,
    cost: 600,
    description: 'High performance laptop',
    image_url: '/images/laptop.jpg',
    is_active: true,
  },
  {
    id: '2',
    name: 'Mouse',
    sku: 'MOU-001',
    category_id: '2',
    price: 29.99,
    cost: 10,
    description: 'Wireless mouse',
    image_url: '/images/mouse.jpg',
    is_active: true,
  },
];

export async function GET(request: NextRequest) {
  const searchParams = request.nextUrl.searchParams;
  const category = searchParams.get('category');
  const search = searchParams.get('search');

  let products = mockProducts;

  if (search) {
    products = products.filter((p) =>
      p.name.toLowerCase().includes(search.toLowerCase())
    );
  }

  if (category) {
    products = products.filter((p) => p.category_id === category);
  }

  return NextResponse.json(products);
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();

    // TODO: Validate and save to database via PHP backend
    const newProduct = {
      id: Date.now().toString(),
      ...body,
    };

    return NextResponse.json(newProduct, { status: 201 });
  } catch (error) {
    return NextResponse.json(
      { error: 'Failed to create product' },
      { status: 400 }
    );
  }
}
