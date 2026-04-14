import { NextRequest, NextResponse } from 'next/server';

const BACKEND_API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost/capstone3-main/api/index.php';

/**
 * Convert camelCase keys to snake_case
 */
function toSnakeCase(obj: any): any {
  if (Array.isArray(obj)) {
    return obj.map(toSnakeCase);
  }
  if (obj !== null && typeof obj === 'object') {
    return Object.keys(obj).reduce((acc, key) => {
      const snakeKey = key.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);
      acc[snakeKey] = toSnakeCase(obj[key]);
      return acc;
    }, {} as any);
  }
  return obj;
}

async function handleTrackRequest(body: any) {
  try {
    // Validate required fields
    if (!body.order_number && !body.orderNumber) {
      return NextResponse.json(
        { success: false, error: 'order_number is required' },
        { status: 400 }
      );
    }

    if (!body.customer_phone && !body.customerPhone && !body.phone) {
      return NextResponse.json(
        { success: false, error: 'customer_phone is required' },
        { status: 400 }
      );
    }

    // Transform to backend format (snake_case)
    const backendData = {
      order_number: body.order_number || body.orderNumber,
      customer_phone: body.customer_phone || body.customerPhone || body.phone,
    };

    console.log('Orders track: Sending to backend:', backendData);

    // Send to backend
    let backendResponse: any;
    try {
      const resp = await fetch(`${BACKEND_API_URL}/orders/track`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(backendData),
      });

      backendResponse = await resp.json().catch(() => ({}));

      if (!resp.ok) {
        console.error('Orders track: Backend returned error', resp.status, backendResponse);
        return NextResponse.json(
          {
            success: false,
            error: backendResponse.error || backendResponse.message || 'Failed to track order',
          },
          { status: resp.status || 500 }
        );
      }
    } catch (e) {
      console.error('Orders track: Failed to communicate with backend', e);
      return NextResponse.json(
        { success: false, error: 'Order tracking service temporarily unavailable' },
        { status: 503 }
      );
    }

    // Return success response
    return NextResponse.json(
      {
        success: true,
        data: backendResponse.data || backendResponse,
        message: 'Order found successfully',
      },
      { status: 200 }
    );
  } catch (error) {
    console.error('Orders track: Unexpected error', error);
    return NextResponse.json(
      {
        success: false,
        error: 'An unexpected error occurred. Please try again later.',
      },
      { status: 500 }
    );
  }
}

export async function GET(request: NextRequest) {
  const backendUrl = new URL(`${BACKEND_API_URL}/orders`);
  request.nextUrl.searchParams.forEach((value, key) => {
    backendUrl.searchParams.append(key, value);
  });

  try {
    const response = await fetch(backendUrl.toString(), {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        ...(request.headers.get('authorization')
          ? { Authorization: request.headers.get('authorization')! }
          : {}),
      },
    });

    if (!response.ok) {
      return NextResponse.json(
        { error: 'Failed to fetch orders' },
        { status: response.status }
      );
    }

    const data = await response.json();
    return NextResponse.json(data);
  } catch (error) {
    console.error('Orders GET error:', error);
    return NextResponse.json(
      { error: 'Failed to fetch orders' },
      { status: 500 }
    );
  }
}

export async function POST(request: NextRequest) {
  console.log('Orders POST: Handler called');
  try {
    // 1. Parse request body
    let body: any;
    try {
      body = await request.json();
      console.log('Orders POST: Parsed body', body);
    } catch (e) {
      console.error('Orders POST: Failed to parse JSON', e);
      return NextResponse.json(
        { success: false, error: 'Invalid JSON in request body' },
        { status: 400 }
      );
    }

    // Check if this is a track request (has order_number and customer_phone but no items)
    if ((body.order_number || body.orderNumber) && (body.customer_phone || body.customerPhone || body.phone) && !body.items) {
      console.log('Orders POST: Detected track request');
      return handleTrackRequest(body);
    }

    // 2. Identify customer (guest or logged-in)
    let customerId: string | null = null;
    let customerType: 'user' | 'guest' = 'guest';
    let guestId: string | null = null;

    // Check for user session/auth header
    const authHeader = request.headers.get('authorization');
    if (authHeader && authHeader.startsWith('Bearer ')) {
      // Try to extract user_id from token (ideally validate token too)
      customerId = authHeader.substring(7); // for now, just use token as ID
      customerType = 'user';
    } else {
      // Fall back to guest_id from cookies
      const guestCookie = request.cookies.get('guest_id');
      if (guestCookie?.value) {
        guestId = guestCookie.value;
      } else {
        // Generate new guest_id
        guestId = `guest_${Date.now()}_${Math.random().toString(36).substring(7)}`;
      }
    }

    // 3. Validate request body
    if (!body.items || !Array.isArray(body.items) || body.items.length === 0) {
      console.error('Orders POST: No items in request');
      return NextResponse.json(
        { success: false, error: 'Cart is empty. Please add items before checkout.' },
        { status: 400 }
      );
    }

    for (const item of body.items) {
      if (!item.productId) {
        console.error('Orders POST: Missing productId in item', item);
        return NextResponse.json(
          { success: false, error: 'Missing product ID in cart item' },
          { status: 400 }
        );
      }
      if (!item.quantity || item.quantity <= 0) {
        console.error('Orders POST: Invalid quantity', item);
        return NextResponse.json(
          { success: false, error: 'Invalid quantity for product' },
          { status: 400 }
        );
      }
    }

    // 4. Fetch products from backend to verify and recalculate
    const productIds = Array.from(new Set(body.items.map((i: any) => i.productId)));
    let productsMap: Record<string, any> = {};

    try {
      for (const productId of productIds) {
        const resp = await fetch(
          `${BACKEND_API_URL}/products/${productId}`,
          { method: 'GET', headers: { 'Content-Type': 'application/json' } }
        );

        if (resp.ok) {
          const productData = await resp.json();
          if (productData.data) {
            productsMap[String(productId)] = productData.data;
          }
        } else {
          console.warn(`Orders POST: Product ${productId} not found`);
        }
      }
    } catch (e) {
      console.error('Orders POST: Failed to fetch products', e);
      return NextResponse.json(
        { success: false, error: 'Unable to verify products. Please try again.' },
        { status: 500 }
      );
    }

    // 5. Recalculate totals (DO NOT trust frontend values)
    let subtotal = 0;
    let hasUnknownProducts = false;
    const processedItems = [];

    for (const item of body.items) {
      const product = productsMap[item.productId];
      
      if (!product) {
        console.warn(`Orders POST: Skipping unknown product ${item.productId}`);
        hasUnknownProducts = true;
        continue;
      }

      const quantity = parseInt(item.quantity) || 0;
      if (quantity <= 0) {
        console.warn(`Orders POST: Skipping item with invalid quantity`);
        continue;
      }

      // Use correct price based on unit type
      let unitPrice = product.retailPrice;
      if (item.unitType === 'wholesale' || item.unitType === 'box') {
        unitPrice = product.wholesalePrice;
      }

      const itemTotal = unitPrice * quantity;
      subtotal += itemTotal;

      processedItems.push({
        productId: product.id,
        productName: product.name,
        quantity,
        unitPrice,
        unitType: item.unitType || 'retail',
      });
    }

    if (processedItems.length === 0) {
      console.error('Orders POST: No valid items after processing');
      return NextResponse.json(
        { success: false, error: 'No valid items in cart' },
        { status: 400 }
      );
    }

    // 6. Calculate final totals (NO TAX - schema doesn't have tax_rate or tax_amount columns)
    const deliveryFee = parseFloat(body.deliveryFee) || 0;
    const total = subtotal + deliveryFee;

    console.log('Orders POST: Calculated totals', { subtotal, deliveryFee, total });

    // 7. Transform to backend format (snake_case) - ONLY include columns that exist in orders table
    const backendOrderData = {
      items: processedItems.map(item => ({
        product_id: item.productId,
        product_name: item.productName,
        quantity: item.quantity,
        unit_type: item.unitType,
        unit_price: item.unitPrice,
        subtotal: item.quantity * item.unitPrice,
      })),
      // Order fields matching schema exactly
      customer_name: body.customerName || null,
      customer_phone: body.customerPhone || null,
      shipping_address: body.shippingAddress || null,
      delivery_fee: deliveryFee,
      delivery_notes: body.notes || null,
      delivery_address: null, // Will be filled by backend if needed
      payment_method: body.paymentMethod || 'cod',
      subtotal,
      discount_amount: 0, // Schema has this with default 0
      total,
      // Only include customer_id if this is a logged-in user (guest orders have NULL customer_id)
      ...(customerType === 'user' && customerId ? { customer_id: customerId } : {}),
    };

    console.log('Orders POST: Data to send to backend:', JSON.stringify(backendOrderData).substring(0, 400));

    // 8. Send to backend
    let backendResponse: any;
    try {
      const resp = await fetch(`${BACKEND_API_URL}/orders`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          ...(authHeader ? { Authorization: authHeader } : {}),
        },
        body: JSON.stringify(backendOrderData),
      });

      backendResponse = await resp.json().catch(() => ({}));

      if (!resp.ok) {
        console.error('Orders POST: Backend returned error', resp.status, backendResponse);
        return NextResponse.json(
          {
            success: false,
            error: backendResponse.error || backendResponse.message || 'Failed to create order',
          },
          { status: resp.status || 500 }
        );
      }

      console.log('Orders POST: Backend response data keys:', Object.keys(backendResponse.data || {}));
      console.log('Orders POST: Backend response data:', JSON.stringify(backendResponse.data).substring(0, 300));
    } catch (e) {
      console.error('Orders POST: Failed to communicate with backend', e);
      return NextResponse.json(
        { success: false, error: 'Order service temporarily unavailable' },
        { status: 503 }
      );
    }

    // 9. Return success response
    const response = NextResponse.json(
      {
        success: true,
        data: backendResponse.data || backendOrderData,
        message: 'Order placed successfully',
      },
      { status: 201 }
    );

    // Set guest_id cookie if guest order
    if (customerType === 'guest' && guestId) {
      response.cookies.set({
        name: 'guest_id',
        value: guestId,
        maxAge: 7 * 24 * 60 * 60, // 7 days
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'lax',
      });
    }

    return response;
  } catch (error) {
    console.error('Orders POST: Unexpected error', error);
    return NextResponse.json(
      {
        success: false,
        error: 'An unexpected error occurred. Please try again later.',
      },
      { status: 500 }
    );
  }
}

export async function PUT(request: NextRequest) {
  const url = new URL(request.url);
  const pathParts = url.pathname.split('/');
  const orderId = pathParts[pathParts.length - 2]; // Get order ID from /api/orders/{id}/cancel
  const action = pathParts[pathParts.length - 1]; // Get action from /api/orders/{id}/cancel

  console.log('Orders PUT: Handler called', { orderId, action });

  try {
    // Parse request body
    let body: any = {};
    try {
      body = await request.json();
    } catch (e) {
      // Body might be empty for some actions
    }

    // Build backend URL
    let backendUrl = `${BACKEND_API_URL}/orders`;
    if (orderId) {
      backendUrl += `/${orderId}`;
      if (action) {
        backendUrl += `/${action}`;
      }
    }

    console.log('Orders PUT: Proxying to backend', backendUrl);

    // Send to backend
    const response = await fetch(backendUrl, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        ...(request.headers.get('authorization')
          ? { Authorization: request.headers.get('authorization')! }
          : {}),
      },
      body: JSON.stringify(body),
    });

    const responseData = await response.json().catch(() => ({}));

    if (!response.ok) {
      console.error('Orders PUT: Backend returned error', response.status, responseData);
      return NextResponse.json(
        {
          success: false,
          error: responseData.error || responseData.message || 'Failed to update order',
        },
        { status: response.status }
      );
    }

    return NextResponse.json(responseData);
  } catch (error) {
    console.error('Orders PUT: Unexpected error', error);
    return NextResponse.json(
      {
        success: false,
        error: 'An unexpected error occurred. Please try again later.',
      },
      { status: 500 }
    );
  }
}
