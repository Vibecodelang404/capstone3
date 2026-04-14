import { NextRequest, NextResponse } from 'next/server'

const BACKEND_API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost/capstone3-main/api/index.php'

const mockCategories = [
  { id: '1', name: 'Snacks', slug: 'snacks', description: 'Grab-and-go favorites', display_order: 1, is_active: true },
  { id: '2', name: 'Beverages', slug: 'beverages', description: 'Drinks and refreshments', display_order: 2, is_active: true },
  { id: '3', name: 'Meals', slug: 'meals', description: 'Hearty meals and combos', display_order: 3, is_active: true },
]

export async function GET(request: NextRequest) {
  const backendUrl = new URL(`${BACKEND_API_URL}/categories`)
  request.nextUrl.searchParams.forEach((value, key) => {
    backendUrl.searchParams.append(key, value)
  })

  try {
    const response = await fetch(backendUrl.toString(), {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        ...(request.headers.get('authorization')
          ? { Authorization: request.headers.get('authorization')! }
          : {}),
      },
    })

    const bodyText = await response.text()
    const contentType = response.headers.get('content-type') || ''

    if (!response.ok) {
      console.warn('Category backend responded with status', response.status, bodyText)
      return NextResponse.json(mockCategories, { status: 200 })
    }

    if (contentType.includes('application/json')) {
      return NextResponse.json(JSON.parse(bodyText), { status: response.status })
    }

    console.warn('Category backend returned non-JSON response', contentType)
    return NextResponse.json(mockCategories, { status: 200 })
  } catch (error) {
    console.warn('Failed to fetch categories from backend:', error)
    return NextResponse.json(mockCategories, { status: 200 })
  }
}
