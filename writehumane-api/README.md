# WriteHumane API

AI Content Humanizer API built with Next.js 14, Prisma, and Stripe.

## Quick Deploy to Vercel + Supabase

### 1. Database Setup (Supabase)

1. Create a free account at [supabase.com](https://supabase.com)
2. Create a new project
3. Go to **Settings → Database** and copy the connection string
4. Replace `[YOUR-PASSWORD]` with your database password
5. Your `DATABASE_URL` will look like: `postgresql://postgres:[PASSWORD]@db.[PROJECT-REF].supabase.co:5432/postgres`

### 2. Stripe Setup

1. Create a Stripe account at [stripe.com](https://stripe.com)
2. Create 3 Products with monthly recurring prices:
   - Starter: $9/month
   - Pro: $19/month
   - Unlimited: $49/month
3. Copy each Price ID (starts with `price_`)
4. Copy your Secret Key and Publishable Key from Developers → API keys
5. Set up a webhook endpoint: `https://your-domain.com/api/webhook`
   - Events to listen for: `checkout.session.completed`, `customer.subscription.updated`, `customer.subscription.deleted`, `invoice.payment_failed`

### 3. AI Provider Setup

- **OpenAI**: Get an API key at [platform.openai.com](https://platform.openai.com)
- **Anthropic** (optional): Get an API key at [console.anthropic.com](https://console.anthropic.com)

### 4. Deploy to Vercel

1. Push this code to a GitHub repository
2. Go to [vercel.com](https://vercel.com) and import the repository
3. Set the **Root Directory** to `writehumane-api`
4. Add all environment variables from `.env.example`
5. Deploy!

### 5. Initialize Database

After deploying, run:
```bash
npx prisma db push
```

Or via Vercel CLI:
```bash
vercel env pull .env.local
npx prisma db push
```

## Local Development

```bash
# Install dependencies
npm install

# Set up environment variables
cp .env.example .env
# Edit .env with your values

# Push database schema
npx prisma db push

# Start dev server
npm run dev
```

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/v1/humanize` | API Key | Humanize text |
| GET | `/api/v1/humanize` | None | Health check |
| POST | `/api/v1/auth/register` | None | Create account |
| POST | `/api/v1/auth/login` | None | Login |
| GET | `/api/v1/auth/api-key` | JWT | List API keys |
| POST | `/api/v1/auth/api-key` | JWT | Create API key |
| DELETE | `/api/v1/auth/api-key?id=xxx` | JWT | Revoke API key |
| GET | `/api/v1/usage` | API Key/JWT | Usage stats |
| POST | `/api/webhook` | Stripe Sig | Stripe webhooks |

## Environment Variables

See `.env.example` for the full list.
