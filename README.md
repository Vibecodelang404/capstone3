# POS Management System

A complete Point-of-Sale (POS) and business management system built with Next.js 16, React 19, and modern web technologies.

## Features

### 1. **Point of Sale (POS)**
- Fast product search and scanning
- Real-time cart management
- Multiple payment methods (Cash, Card, Check)
- Transaction history and receipt generation
- Quick checkout interface

### 2. **Product Management**
- Complete product catalog
- SKU tracking
- Dynamic pricing with margin calculations
- Product categorization
- Bulk operations

### 3. **Inventory Management**
- Real-time stock tracking
- Automated low-stock alerts
- Reorder level management
- Stock adjustment capabilities
- Inventory analytics

### 4. **Supplier Orders**
- Purchase order creation and tracking
- Supplier management
- Order status tracking (Pending, Confirmed, Shipped, Delivered)
- Expected delivery dates
- Order notes and documentation

### 5. **Analytics & Reports**
- Daily sales charts
- Payment method breakdown
- Transaction history
- Revenue metrics
- Export capabilities

### 6. **User Management**
- Role-based access control
- Staff management
- Activity tracking
- Secure authentication

## Technology Stack

- **Frontend:** Next.js 16, React 19.2.4
- **Styling:** Tailwind CSS v4
- **UI Components:** shadcn/ui + Radix UI
- **Charts:** Recharts
- **State Management:** React Context API
- **API Communication:** Fetch API with Service Layer
- **Authentication:** JWT-based (ready for backend integration)
- **Icons:** Lucide React

## Project Structure

```
app/
├── api/
│   ├── auth/route.ts          # Authentication endpoints
│   ├── products/route.ts       # Product endpoints
│   ├── transactions/route.ts   # Transaction endpoints
│   ├── inventory/route.ts      # Inventory endpoints
│   └── orders/route.ts         # Order endpoints
├── dashboard/page.tsx          # Main dashboard
├── pos/page.tsx                # Point of Sale interface
├── products/page.tsx           # Product management
├── inventory/page.tsx          # Inventory management
├── orders/page.tsx             # Supplier orders
├── reports/page.tsx            # Analytics and reports
├── login/page.tsx              # Login page
└── layout.tsx                  # Root layout with providers

lib/
├── api-service.ts              # API service layer
└── contexts/
    ├── auth-context.tsx        # Authentication context
    ├── product-context.tsx     # Product state management
    ├── transaction-context.tsx # Transaction state management
    └── inventory-context.tsx   # Inventory state management

components/
├── protected-route.tsx         # Protected route wrapper
└── ui/                         # shadcn/ui components
```

## Getting Started

### Prerequisites
- Node.js 18+ (or Bun, Deno)
- npm/pnpm/yarn package manager

### Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd pos-system
```

2. Install dependencies:
```bash
pnpm install
```

3. Create environment variables:
```bash
cp .env.example .env.local
```

4. Start the development server:
```bash
pnpm dev
```

5. Open [http://localhost:3000](http://localhost:3000) in your browser

### Login Credentials

Demo credentials for testing:
- Email: `demo@example.com`
- Password: `password123`

## API Integration

The system is designed with a service layer (`lib/api-service.ts`) that abstracts API calls. The current implementation includes mock data for demonstration.

### Connecting to Backend

To connect to your PHP or Node.js backend:

1. Update `lib/api-service.ts`:
```typescript
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:3000/api';
```

2. Set the environment variable in `.env.local`:
```
NEXT_PUBLIC_API_URL=http://your-backend-url/api
```

3. Update the route handlers in `app/api/*/route.ts` to forward requests to your backend

## Key Features Implementation

### Authentication
- JWT-based authentication with token storage
- Protected routes with automatic redirects
- User context hooks for accessing auth state

### Real-time State Management
- Context API for global state
- Service layer for API calls
- Automatic data synchronization

### Responsive Design
- Mobile-first approach
- Tailwind CSS responsive utilities
- Touch-friendly interface for tablet/mobile POS terminals

## Development

### Adding New Pages

1. Create a new file in `app/<feature>/page.tsx`
2. Wrap with `<ProtectedRoute>` component
3. Use context hooks for data management
4. Import UI components from `@/components/ui`

### Adding New API Endpoints

1. Create route handler in `app/api/<resource>/route.ts`
2. Add corresponding service methods in `lib/api-service.ts`
3. Create context provider if needed in `lib/contexts/`
4. Use hooks in components

### Styling

- Use Tailwind CSS classes for styling
- Follow the design tokens defined in `app/globals.css`
- Use semantic color classes (e.g., `text-foreground`, `bg-card`)
- Maintain consistent spacing using Tailwind scale

## Database Schema

The system is designed to work with the following database structure:

- **users**: User accounts and roles
- **products**: Product catalog with pricing
- **categories**: Product categorization
- **inventory**: Stock tracking
- **transactions**: Sales transactions
- **transaction_items**: Line items for transactions
- **orders**: Supplier purchase orders
- **order_items**: Line items for orders
- **suppliers**: Supplier information
- **batches**: Product batch tracking

See `user_read_only_context/text_attachments/query-JIpN9.sql` for complete schema definition.

## Deployment

### Deploy to Vercel

1. Push code to GitHub
2. Connect repository to Vercel
3. Set environment variables
4. Deploy

```bash
vercel deploy
```

### Environment Variables for Production

```
NEXT_PUBLIC_API_URL=https://your-backend-domain/api
NODE_ENV=production
```

## Security Considerations

1. **API Authentication**: All protected endpoints require JWT tokens
2. **CORS**: Configure CORS headers appropriately
3. **Input Validation**: Implement validation on both frontend and backend
4. **SQL Injection**: Use parameterized queries on backend
5. **Rate Limiting**: Implement rate limiting on API endpoints
6. **HTTPS**: Always use HTTPS in production
7. **Secrets**: Never commit secrets to version control

## Performance Optimization

- Server-side rendering for initial load
- Client-side data caching with Context API
- Lazy loading for heavy components
- Image optimization
- Code splitting with dynamic imports

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Login Issues
- Clear browser cache and cookies
- Check if backend API is running
- Verify environment variables

### API Errors
- Check network tab in browser dev tools
- Verify CORS headers
- Ensure backend is responding correctly

### Performance Issues
- Clear Next.js cache: `rm -rf .next`
- Clear node_modules: `rm -rf node_modules && pnpm install`
- Check browser console for errors

## Contributing

1. Create a feature branch
2. Make changes
3. Test thoroughly
4. Create pull request

## License

MIT License - feel free to use this for your business

## Support

For issues and questions:
1. Check existing GitHub issues
2. Create a new issue with details
3. Contact support team

## Future Enhancements

- [ ] Advanced reporting with custom date ranges
- [ ] Customer loyalty program
- [ ] Multi-location support
- [ ] Real-time notifications
- [ ] Mobile app
- [ ] Barcode generation
- [ ] Advanced inventory forecasting
- [ ] Integration with payment gateways
- [ ] Tax calculations
- [ ] Multi-currency support
