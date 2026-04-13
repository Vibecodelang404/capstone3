import { NextRequest, NextResponse } from 'next/server';
import { SignJWT } from 'jose';
import { cookies } from 'next/headers';

// Demo users for testing - in production, this would be a database
const DEMO_USERS = [
  {
    id: 'usr_001',
    name: 'Admin User',
    email: 'admin@mystore.com',
    password: 'admin123',
    role: 'admin',
  },
  {
    id: 'usr_002',
    name: 'Juan Dela Cruz',
    email: 'stock@mystore.com',
    password: 'stock123',
    role: 'stockman',
  },
  {
    id: 'usr_003',
    name: 'Maria Santos',
    email: 'cashier@mystore.com',
    password: 'cashier123',
    role: 'cashier',
  },
  {
    id: 'usr_006',
    name: 'Pedro Reyes',
    email: 'customer@email.com',
    password: 'customer123',
    role: 'customer',
  },
];

const JWT_SECRET = new TextEncoder().encode(
  process.env.JWT_SECRET || 'your-secret-key-change-in-production'
);

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { email, password } = body;

    if (!email || !password) {
      return NextResponse.json(
        { message: 'Email and password are required' },
        { status: 400 }
      );
    }

    // Find user
    const user = DEMO_USERS.find(
      (u) => u.email === email && u.password === password
    );

    if (!user) {
      return NextResponse.json(
        { message: 'Invalid email or password' },
        { status: 401 }
      );
    }

    // Create JWT token
    const token = await new SignJWT({
      userId: user.id,
      email: user.email,
      role: user.role,
    })
      .setProtectedHeader({ alg: 'HS256' })
      .setIssuedAt()
      .setExpirationTime('24h')
      .sign(JWT_SECRET);

    // Set cookie
    const cookieStore = await cookies();
    cookieStore.set('auth_token', token, {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      sameSite: 'lax',
      maxAge: 60 * 60 * 24, // 24 hours
      path: '/',
    });

    // Return user data (without password) and token
    const { password: _, ...userWithoutPassword } = user;

    return NextResponse.json({
      user: userWithoutPassword,
      token,
      message: 'Login successful',
    });
  } catch (error) {
    console.error('Login error:', error);
    return NextResponse.json(
      { message: 'Internal server error' },
      { status: 500 }
    );
  }
}
