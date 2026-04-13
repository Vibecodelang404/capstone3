import { NextRequest, NextResponse } from 'next/server';

// Auth endpoints
export async function POST(request: NextRequest) {
  const path = request.nextUrl.pathname;

  if (path === '/api/auth/login') {
    try {
      const body = await request.json();
      const { email, password } = body;

      // TODO: Call your PHP backend API
      // For now, returning mock data
      return NextResponse.json({
        token: 'jwt-token-here',
        user: {
          id: '1',
          name: 'Test User',
          email,
          role: 'admin',
        },
      });
    } catch (error) {
      return NextResponse.json(
        { error: 'Invalid credentials' },
        { status: 401 }
      );
    }
  }

  if (path === '/api/auth/register') {
    try {
      const body = await request.json();
      const { name, email, password } = body;

      // TODO: Call your PHP backend API
      return NextResponse.json({
        token: 'jwt-token-here',
        user: {
          id: '1',
          name,
          email,
          role: 'user',
        },
      });
    } catch (error) {
      return NextResponse.json(
        { error: 'Registration failed' },
        { status: 400 }
      );
    }
  }

  return NextResponse.json({ error: 'Not found' }, { status: 404 });
}

export async function GET(request: NextRequest) {
  const path = request.nextUrl.pathname;

  if (path === '/api/auth/me') {
    const authHeader = request.headers.get('Authorization');
    if (!authHeader?.startsWith('Bearer ')) {
      return NextResponse.json(
        { error: 'Unauthorized' },
        { status: 401 }
      );
    }

    // TODO: Validate JWT and return user data
    return NextResponse.json({
      id: '1',
      name: 'Test User',
      email: 'test@example.com',
      role: 'admin',
    });
  }

  return NextResponse.json({ error: 'Not found' }, { status: 404 });
}
