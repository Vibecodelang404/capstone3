import { NextRequest, NextResponse } from 'next/server';

const BACKEND_API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost/capstone3-main/api/index.php';

export async function POST(request: NextRequest) {
  console.log('Orders track: Handler called');
  try {
    // Parse request body
    let body: any;
    try {
      body = await request.json();
      console.log('Orders track: Parsed body', body);
    } catch (e) {
      console.error('Orders track: Failed to parse JSON', e);
      return NextResponse.json(
        { success: false, error: 'Invalid JSON in request body' },
        { status: 400 }
      );
    }

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