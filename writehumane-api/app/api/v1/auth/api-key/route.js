import { NextResponse } from 'next/server';
import { authenticateJWT, generateApiKey } from '@/lib/auth';
import prisma from '@/lib/db';

function maskKey(key) {
  if (key.length < 16) return key;
  return key.substring(0, 12) + '...' + key.substring(key.length - 4);
}

export async function GET(request) {
  try {
    const user = await authenticateJWT(request);
    if (!user) {
      return NextResponse.json({ success: false, error: 'Unauthorized' }, { status: 401 });
    }

    const apiKeys = await prisma.apiKey.findMany({
      where: { userId: user.id },
      orderBy: { createdAt: 'desc' },
    });

    return NextResponse.json({
      success: true,
      api_keys: apiKeys.map((k) => ({
        id: k.id,
        name: k.name,
        key_masked: maskKey(k.key),
        key: k.key,
        isActive: k.isActive,
        lastUsedAt: k.lastUsedAt,
        createdAt: k.createdAt,
      })),
    });
  } catch (error) {
    console.error('API key list error:', error);
    return NextResponse.json({ success: false, error: 'Server error.' }, { status: 500 });
  }
}

export async function POST(request) {
  try {
    const user = await authenticateJWT(request);
    if (!user) {
      return NextResponse.json({ success: false, error: 'Unauthorized' }, { status: 401 });
    }

    // Check max keys
    const keyCount = await prisma.apiKey.count({ where: { userId: user.id } });
    if (keyCount >= 5) {
      return NextResponse.json({ success: false, error: 'Maximum 5 API keys per account.' }, { status: 400 });
    }

    const body = await request.json().catch(() => ({}));
    const keyValue = generateApiKey();

    const apiKey = await prisma.apiKey.create({
      data: {
        key: keyValue,
        name: body.name || `Key ${keyCount + 1}`,
        userId: user.id,
      },
    });

    return NextResponse.json({
      success: true,
      api_key: {
        id: apiKey.id,
        name: apiKey.name,
        key: keyValue,
        isActive: true,
        createdAt: apiKey.createdAt,
      },
      warning: 'Save this API key now. For security, the full key is only shown once at creation.',
    });
  } catch (error) {
    console.error('API key create error:', error);
    return NextResponse.json({ success: false, error: 'Server error.' }, { status: 500 });
  }
}

export async function DELETE(request) {
  try {
    const user = await authenticateJWT(request);
    if (!user) {
      return NextResponse.json({ success: false, error: 'Unauthorized' }, { status: 401 });
    }

    const { searchParams } = new URL(request.url);
    const keyId = searchParams.get('id');

    if (!keyId) {
      return NextResponse.json({ success: false, error: 'Missing key ID.' }, { status: 400 });
    }

    const apiKey = await prisma.apiKey.findFirst({
      where: { id: keyId, userId: user.id },
    });

    if (!apiKey) {
      return NextResponse.json({ success: false, error: 'API key not found.' }, { status: 404 });
    }

    await prisma.apiKey.update({
      where: { id: keyId },
      data: { isActive: false },
    });

    return NextResponse.json({ success: true, message: 'API key revoked.' });
  } catch (error) {
    console.error('API key delete error:', error);
    return NextResponse.json({ success: false, error: 'Server error.' }, { status: 500 });
  }
}
