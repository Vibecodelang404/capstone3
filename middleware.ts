import type { NextRequest } from 'next/server';
import { NextResponse } from 'next/server';

// This is the main API router that will handle all requests
export async function middleware(request: NextRequest) {
  const path = request.nextUrl.pathname;

  // Handle CORS preflight requests
  if (request.method === 'OPTIONS') {
    return new NextResponse(null, {
      status: 200,
      headers: {
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers': 'Content-Type, Authorization',
      },
    });
  }

  // Auth check for protected routes (not auth routes)
  if (
    path.startsWith('/api/products') ||
    path.startsWith('/api/transactions') ||
    path.startsWith('/api/inventory') ||
    path.startsWith('/api/orders')
  ) {
    const authHeader = request.headers.get('Authorization');
    if (!authHeader?.startsWith('Bearer ')) {
      return NextResponse.json(
        { error: 'Unauthorized' },
        { 
          status: 401, 
          headers: {
            'Access-Control-Allow-Origin': '*',
          }
        }
      );
    }
  }

  // Create response with CORS headers
  const response = NextResponse.next();
  response.headers.set('Access-Control-Allow-Origin', '*');
  response.headers.set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  response.headers.set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
  
  return response;
}

export const config = {
  matcher: ['/api/:path*'],
};
