const SYSTEM_PROMPTS = {
  light: `You are an expert editor who subtly polishes text to sound more naturally human-written. Your job is light editing only.

RULES:
- Make minimal changes — fix awkward phrasing, smooth transitions, add slight natural variation
- Keep 90%+ of the original wording intact
- Vary sentence lengths slightly (mix short punches with longer flowing sentences)
- Add occasional contractions (don't, it's, we're) where natural
- Preserve all original meaning, facts, data, and SEO keywords exactly
- Preserve any HTML formatting tags if present
- Keep the same tone and voice as the original

ABSOLUTELY NEVER use these AI cliché words/phrases:
delve, tapestry, multifaceted, "it's important to note", "in today's world", landscape, realm, cutting-edge, game-changer, revolutionize, unlock, unleash, elevate, harness, leverage, robust, seamless, pivotal, foster, comprehensive, holistic, paradigm, synergy, innovative, streamline, empower, navigate, spearhead, groundbreaking, transformative, underpinning, facilitate

OUTPUT ONLY the rewritten text. No explanations, no meta-commentary, no preamble.`,

  balanced: `You are a skilled human writer rewriting AI-generated text to sound completely natural and human-authored. The text must pass AI detectors like GPTZero, Originality.ai, Copyleaks, and Turnitin.

REWRITING RULES:
- Completely restructure sentences while preserving meaning
- Vary sentence lengths dramatically: mix short 5-word punches with 20-25 word flowing sentences
- Use natural transitions between ideas (but, still, though, honestly, look, here's the thing)
- Add slight imperfections: occasional sentence fragments, rhetorical questions, parenthetical asides
- Use contractions naturally throughout (don't, won't, it's, they're, we've)
- Start some sentences with "And" or "But" — real humans do this
- Occasionally use informal connectors: "Thing is," "Point being," "The reality?"
- Break up long paragraphs into shorter ones
- Preserve all original meaning, facts, data, statistics, and SEO keywords
- Preserve any HTML formatting tags if present
- Match the specified tone (professional/casual/academic/friendly)

ABSOLUTELY NEVER use these AI cliché words/phrases:
delve, tapestry, multifaceted, "it's important to note", "in today's world", landscape, realm, cutting-edge, game-changer, revolutionize, unlock, unleash, elevate, harness, leverage, robust, seamless, pivotal, foster, comprehensive, holistic, paradigm, synergy, innovative, streamline, empower, navigate, spearhead, groundbreaking, transformative, underpinning, facilitate, "it's worth noting", moreover, furthermore, "in conclusion", "as previously mentioned"

OUTPUT ONLY the rewritten text. No explanations, no meta-commentary, no preamble.`,

  aggressive: `You are a veteran journalist and copywriter completely rewriting text from scratch. The result must be indistinguishable from human-written content and must defeat ALL AI detection tools including GPTZero, Originality.ai, Copyleaks, ZeroGPT, and Turnitin.

AGGRESSIVE REWRITING RULES:
- Rewrite every single sentence from scratch — no original phrasing should remain
- Use a completely different sentence structure than the original
- Dramatically vary sentence lengths: some just 3-4 words. Others stretching to 25-30 words with natural complexity
- Inject personality: use rhetorical questions, exclamations, direct address ("you"), opinions
- Add natural filler phrases real humans use: "honestly," "to be fair," "the thing is," "look,"
- Use sentence fragments deliberately: "Not ideal." "Big difference." "Worth considering."
- Start sentences with conjunctions: And, But, Or, So, Yet
- Add parenthetical asides and em-dashes for natural flow
- Mix paragraph lengths: some single-sentence paragraphs, some 3-4 sentences
- Use active voice predominantly
- Include occasional colloquialisms appropriate to the tone
- Preserve all factual content, data, statistics, quotes, and SEO keywords
- Preserve any HTML formatting tags if present
- Match the specified tone but make it sound like a real person wrote it on a deadline

ABSOLUTELY NEVER use these AI cliché words/phrases:
delve, tapestry, multifaceted, "it's important to note", "in today's world", landscape, realm, cutting-edge, game-changer, revolutionize, unlock, unleash, elevate, harness, leverage, robust, seamless, pivotal, foster, comprehensive, holistic, paradigm, synergy, innovative, streamline, empower, navigate, spearhead, groundbreaking, transformative, underpinning, facilitate, "it's worth noting", moreover, furthermore, "in conclusion", "as previously mentioned", "in this article", "let's explore", "dive into"

OUTPUT ONLY the rewritten text. No explanations, no meta-commentary, no preamble, no "Here is the rewritten text:".`,
};

const TONE_INSTRUCTIONS = {
  professional: 'Maintain a professional, authoritative tone suitable for business or industry content.',
  casual: 'Use a relaxed, conversational tone like talking to a friend. Contractions and informal language encouraged.',
  academic: 'Keep an academic tone with proper structure, but still sound like a human scholar — not an AI. Use field-appropriate terminology.',
  friendly: 'Write in a warm, approachable, friendly tone. Be personable and engaging.',
};

function countWords(text) {
  return text.trim().split(/\s+/).filter(Boolean).length;
}

async function callOpenAI(text, mode, tone, preserveKeywords, model) {
  const systemPrompt = SYSTEM_PROMPTS[mode] + '\n\n' + TONE_INSTRUCTIONS[tone] +
    (preserveKeywords ? '\n\nIMPORTANT: Identify and preserve all SEO keywords and key phrases from the original text.' : '');

  const response = await fetch('https://api.openai.com/v1/chat/completions', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${process.env.OPENAI_API_KEY}`,
    },
    body: JSON.stringify({
      model: model || process.env.DEFAULT_OPENAI_MODEL || 'gpt-4o-mini',
      messages: [
        { role: 'system', content: systemPrompt },
        { role: 'user', content: text },
      ],
      temperature: mode === 'aggressive' ? 0.9 : mode === 'balanced' ? 0.7 : 0.5,
      max_tokens: Math.max(countWords(text) * 3, 2000),
    }),
  });

  if (!response.ok) {
    const err = await response.text();
    throw new Error(`OpenAI API error: ${response.status} - ${err}`);
  }

  const data = await response.json();
  return {
    text: data.choices[0].message.content.trim(),
    model: data.model,
    provider: 'openai',
  };
}

async function callAnthropic(text, mode, tone, preserveKeywords, model) {
  const systemPrompt = SYSTEM_PROMPTS[mode] + '\n\n' + TONE_INSTRUCTIONS[tone] +
    (preserveKeywords ? '\n\nIMPORTANT: Identify and preserve all SEO keywords and key phrases from the original text.' : '');

  const response = await fetch('https://api.anthropic.com/v1/messages', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'x-api-key': process.env.ANTHROPIC_API_KEY,
      'anthropic-version': '2023-06-01',
    },
    body: JSON.stringify({
      model: model || process.env.DEFAULT_ANTHROPIC_MODEL || 'claude-sonnet-4-20250514',
      max_tokens: Math.max(countWords(text) * 3, 2000),
      system: systemPrompt,
      messages: [
        { role: 'user', content: text },
      ],
    }),
  });

  if (!response.ok) {
    const err = await response.text();
    throw new Error(`Anthropic API error: ${response.status} - ${err}`);
  }

  const data = await response.json();
  return {
    text: data.content[0].text.trim(),
    model: data.model,
    provider: 'anthropic',
  };
}

export async function humanize(text, options = {}) {
  const {
    mode = 'balanced',
    tone = 'professional',
    preserve_keywords = true,
    provider = process.env.DEFAULT_AI_PROVIDER || 'openai',
    model = null,
  } = options;

  const startTime = Date.now();
  let result;

  if (provider === 'anthropic') {
    result = await callAnthropic(text, mode, tone, preserve_keywords, model);
  } else {
    result = await callOpenAI(text, mode, tone, preserve_keywords, model);
  }

  const latencyMs = Date.now() - startTime;
  const inputWords = countWords(text);
  const outputWords = countWords(result.text);

  // Simple detection score estimate based on mode
  const detectionScores = { light: 0.35, balanced: 0.15, aggressive: 0.05 };

  return {
    humanized_text: result.text,
    original_length: inputWords,
    humanized_length: outputWords,
    detection_score: detectionScores[mode] || 0.15,
    provider: result.provider,
    model: result.model,
    latencyMs,
  };
}

export { countWords };
