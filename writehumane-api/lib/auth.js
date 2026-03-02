import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';
import { cookies } from 'next/headers';
import prisma from './db';

export const PLAN_LIMITS = {
  FREE: { monthlyWords: 1000, ratePerMinute: 5, name: 'Free', price: 0 },
  STARTER: { monthlyWords: 10000, ratePerMinute: 15, name: 'Starter', price: 9 },
  PRO: { monthlyWords: 50000, ratePerMinute: 30, name: 'Pro', price: 19 },
  UNLIMITED: { monthlyWords: 999999, ratePerMinute: 60, name: 'Unlimited', price: 49 },
};

export async function hashPassword(password) {
  return bcrypt.hash(password, 12);
}

export async function verifyPassword(password, hash) {
  return bcrypt.compare(password, hash);
}

export function signToken(payload) {
  return jwt.sign(payload, process.env.JWT_SECRET, { expiresIn: '30d' });
}

export function verifyToken(token) {
  try {
    return jwt.verify(token, process.env.JWT_SECRET);
  } catch {
    return null;
  }
}

export function generateApiKey() {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  let result = 'wh_live_';
  for (let i = 0; i < 40; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return result;
}

export async function authenticateApiKey(request) {
  const authHeader = request.headers.get('authorization');
  if (!authHeader?.startsWith('Bearer ')) return null;

  const key = authHeader.slice(7);
  if (!key.startsWith('wh_live_')) return null;

  const apiKey = await prisma.apiKey.findUnique({
    where: { key },
    include: { user: true },
  });

  if (!apiKey || !apiKey.isActive) return null;

  await prisma.apiKey.update({
    where: { id: apiKey.id },
    data: { lastUsedAt: new Date() },
  });

  return apiKey.user;
}

export async function authenticateJWT(request) {
  let token = null;

  // Try cookie first
  try {
    const cookieStore = await cookies();
    token = cookieStore.get('token')?.value;
  } catch {
    // cookies() may not be available in all contexts
  }

  // Fallback to Authorization header
  if (!token) {
    const authHeader = request.headers.get('authorization');
    if (authHeader?.startsWith('Bearer ')) {
      token = authHeader.slice(7);
    }
  }

  if (!token) return null;

  const decoded = verifyToken(token);
  if (!decoded?.userId) return null;

  const user = await prisma.user.findUnique({
    where: { id: decoded.userId },
  });

  return user;
}

export async function checkWordBudget(user, requestedWords) {
  const now = new Date();
  const resetAt = user.creditResetAt ? new Date(user.creditResetAt) : null;

  // Reset monthly counter if new month
  if (!resetAt || now >= resetAt) {
    const nextReset = new Date(now.getFullYear(), now.getMonth() + 1, 1);
    await prisma.user.update({
      where: { id: user.id },
      data: {
        wordsUsedThisMonth: 0,
        creditResetAt: nextReset,
      },
    });
    user.wordsUsedThisMonth = 0;
    user.creditResetAt = nextReset;
  }

  const limit = PLAN_LIMITS[user.plan]?.monthlyWords ?? 1000;
  const remaining = limit - user.wordsUsedThisMonth;
  return remaining >= requestedWords;
}

export async function deductWords(userId, words) {
  await prisma.user.update({
    where: { id: userId },
    data: {
      wordsUsedThisMonth: { increment: words },
    },
  });
}
