<?php
/**
 * Text Toolkit — Gemini AI backend
 * POST /plugins/text-toolkit/api
 * Body: { tool: string, text: string, params: object }
 */
defined('AWAN') or define('AWAN', true);

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST required']); exit;
}

$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$tool   = trim((string)($body['tool']   ?? ''));
$text   = trim((string)($body['text']   ?? ''));
$params = (array)($body['params']       ?? []);

$apiKey = trim((string)($body['gemini_key'] ?? ''));
if (!$apiKey) {
    echo json_encode(['error' => 'no_key']);
    exit;
}
// Basic sanity check — Gemini keys start with "AIza"
if (!preg_match('/^AIza[A-Za-z0-9_\-]{30,}$/', $apiKey)) {
    echo json_encode(['error' => 'The API key looks invalid. Gemini keys start with "AIza".']);
    exit;
}

/* ─── Param helper ───────────────────────────────────────────────────────── */
function gp(array $p, string $k, string $def = ''): string {
    return trim((string)($p[$k] ?? $def));
}

/* ─── Tool prompts ───────────────────────────────────────────────────────── */
$tools = [

    // ═══════════════════════════ WRITING ════════════════════════════════════

    'headline' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        return "Generate 5 compelling, click-worthy headlines for the following topic. Vary the styles: how-to, numbered list, bold statement, question, and curiosity gap. Output as a numbered list only.\n\nTopic:\n{$topic}";
    },
    'blog_title' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        return "Generate 5 creative, SEO-friendly blog post title ideas for the following topic. Vary formats: ultimate guide, tips list, case study, story-driven, and data-driven. Output as a numbered list only.\n\nTopic:\n{$topic}";
    },
    'article_title' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        return "Generate 5 professional article titles for the following topic. Mix journalistic and analytical styles. Output as a numbered list only.\n\nTopic:\n{$topic}";
    },
    'yt_title' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        return "Generate 5 YouTube video titles optimized for clicks and search for: \"{$topic}\". Include emotion, numbers, or power words where natural. Output as a numbered list only.";
    },
    'email_subject' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        return "Generate 5 email subject lines for the following topic. Mix urgency, curiosity, personalization, and benefit-driven styles. Output as a numbered list only.\n\nTopic:\n{$topic}";
    },
    'ad_headline' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        return "Generate 5 Google Ads headlines (max 30 characters each) for: \"{$topic}\". Focus on the main benefit or USP. Output as a numbered list with character count in parentheses.";
    },
    'intro_gen' => function($t,$p) {
        if (!$t) return "Error: Paste your content in the Workbench first.";
        return "Write a strong, engaging introduction paragraph for the following content. Hook the reader immediately, establish relevance, and preview what follows. Return only the introduction paragraph.\n\nContent:\n{$t}";
    },
    'conclusion_gen' => function($t,$p) {
        if (!$t) return "Error: Paste your content in the Workbench first.";
        return "Write a compelling conclusion paragraph for the following content. Summarize key points, provide closure, and end with a memorable takeaway or CTA. Return only the conclusion paragraph.\n\nContent:\n{$t}";
    },
    'outline_gen' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        return "Create a detailed content outline for the following topic. Include H1, H2, and H3 headings with brief notes on what each section covers. Use markdown heading formatting.\n\nTopic/content:\n{$topic}";
    },
    'para_gen' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        return "Write a well-structured, informative paragraph (100–150 words) about the following topic. Make it engaging.\n\nTopic:\n{$topic}";
    },
    'hook_gen' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        return "Write 5 attention-grabbing opening hooks for the following topic. Vary the styles: shocking statistic, bold statement, question, story opening, contrarian view. Output as a numbered list only.\n\nTopic:\n{$topic}";
    },
    'cta_gen' => function($t,$p) {
        $action = gp($p,'action','sign up');
        return "Generate 5 compelling call-to-action phrases for the action: \"{$action}\". Make them urgent, benefit-focused, and varied in style. Output as a numbered list only.\n\nContext:\n{$t}";
    },
    'summary_gen' => function($t,$p) {
        if (!$t) return "Error: Paste your content in the Workbench first.";
        return "Write a concise 2–3 sentence summary of the following text. Capture the main points and key takeaways only.\n\nText:\n{$t}";
    },
    'product_desc' => function($t,$p) {
        $product = gp($p,'product') ?: $t;
        if (!$product) return "Error: Enter a product name or paste details in the Workbench.";
        return "Write a compelling 100–150 word product description for the following. Highlight key benefits (not just features), address pain points, and close with a clear value proposition.\n\nProduct:\n{$product}";
    },
    'sales_copy' => function($t,$p) {
        if (!$t) return "Error: Paste your product/service details in the Workbench first.";
        return "Write persuasive sales copy for the following product/service. Use AIDA structure (Attention, Interest, Desire, Action). Keep it punchy and conversion-focused.\n\nContent:\n{$t}";
    },
    'landing_copy' => function($t,$p) {
        if (!$t) return "Error: Paste your product/service details in the Workbench first.";
        return "Write landing page hero copy for the following product/service:\n1. Headline (under 10 words)\n2. Subheadline (1–2 sentences)\n3. 3 key benefit bullet points\n4. CTA button text\n\nProduct/service:\n{$t}";
    },
    'ad_copy' => function($t,$p) {
        $platform = gp($p,'platform','social media');
        if (!$t) return "Error: Paste your product/service details in the Workbench first.";
        return "Write compelling {$platform} ad copy for the following. Include: headline, body copy (2–3 sentences), and CTA.\n\nProduct/service:\n{$t}";
    },
    'fb_ad' => function($t,$p) {
        if (!$t) return "Error: Paste your product/service details in the Workbench first.";
        return "Write a Facebook ad for the following product/service:\n- Primary text (2–3 sentences: hook + benefit + CTA)\n- Headline (under 40 chars)\n- Description (under 30 chars)\n\nProduct/service:\n{$t}";
    },
    'google_ad' => function($t,$p) {
        if (!$t) return "Error: Paste your product/service details in the Workbench first.";
        return "Write 3 Google Responsive Search Ad variations for the following product/service. Each needs:\n- Headline 1 (max 30 chars)\n- Headline 2 (max 30 chars)\n- Headline 3 (max 30 chars)\n- Description (max 90 chars)\n\nProduct/service:\n{$t}";
    },
    'biz_name' => function($t,$p) {
        $industry = gp($p,'industry') ?: $t;
        if (!$industry) return "Error: Enter an industry or concept in the Industry field.";
        return "Generate 10 unique, memorable business name ideas for: \"{$industry}\". Mix modern, classic, descriptive, and abstract styles. Include a one-line explanation for each. Output as a numbered list.";
    },
    'brand_name' => function($t,$p) {
        $concept = gp($p,'concept') ?: $t;
        if (!$concept) return "Error: Enter a concept or paste content in the Workbench.";
        return "Generate 10 strong brand name ideas for: \"{$concept}\". They should be short (1–2 words), memorable, and domain-friendly. Briefly explain the appeal of each. Output as a numbered list.";
    },
    'slogan' => function($t,$p) {
        $brand = gp($p,'brand') ?: $t;
        if (!$brand) return "Error: Enter a brand name or paste content in the Workbench.";
        return "Generate 5 memorable slogans for: \"{$brand}\". Each should be under 8 words, catchy, and convey the brand's core value. Output as a numbered list only.";
    },
    'tagline' => function($t,$p) {
        $brand = gp($p,'brand') ?: $t;
        if (!$brand) return "Error: Enter a brand name or paste content in the Workbench.";
        return "Generate 5 compelling taglines for: \"{$brand}\". They should be concise, evocative, and differentiated. Output as a numbered list only.";
    },
    'mission_stmt' => function($t,$p) {
        $company = gp($p,'company') ?: $t;
        if (!$company) return "Error: Enter company details or paste content in the Workbench.";
        return "Write a clear, inspiring 1–2 sentence mission statement for: \"{$company}\". Explain what you do, who you serve, and why it matters.";
    },
    'vision_stmt' => function($t,$p) {
        $company = gp($p,'company') ?: $t;
        if (!$company) return "Error: Enter company details or paste content in the Workbench.";
        return "Write an ambitious, inspiring 1–2 sentence vision statement for: \"{$company}\". Describe the future state you're working toward.";
    },
    'elevator_pitch' => function($t,$p) {
        if (!$t) return "Error: Paste your product/idea details in the Workbench first.";
        return "Write a 30-second elevator pitch (under 80 words) for the following. Structure: problem → solution → unique value → call to action.\n\nProduct/idea:\n{$t}";
    },

    // ═══════════════════════════ GRAMMAR ════════════════════════════════════

    'grammar_check' => function($t,$p) {
        if (!$t) return "Error: Paste your text in the Workbench first.";
        return "Check the following text for grammar errors. For each error provide: the original phrase, the issue, and the correction. If no errors, say so clearly. Format as a numbered list.\n\nText:\n{$t}";
    },
    'spelling_check' => function($t,$p) {
        if (!$t) return "Error: Paste your text in the Workbench first.";
        return "Check the following text for spelling errors. List each misspelled word with the correct spelling and a brief context note. If no errors, say so clearly.\n\nText:\n{$t}";
    },
    'punct_check' => function($t,$p) {
        if (!$t) return "Error: Paste your text in the Workbench first.";
        return "Check the following text for punctuation errors and inconsistencies. List each issue with the offending text, the problem, and the correction.\n\nText:\n{$t}";
    },
    'cap_check' => function($t,$p) {
        if (!$t) return "Error: Paste your text in the Workbench first.";
        return "Check the following text for capitalization errors (sentence starts, proper nouns, title case). List each error with correction. If none, say so.\n\nText:\n{$t}";
    },
    'sentence_correct' => function($t,$p) {
        if (!$t) return "Error: Paste your text in the Workbench first.";
        return "Fix all grammatical, spelling, and punctuation errors in the following text. Return only the fully corrected text with no explanations or commentary.\n\nText:\n{$t}";
    },
    'passive_check' => function($t,$p) {
        if (!$t) return "Error: Paste your text in the Workbench first.";
        return "Identify all passive voice sentences in the following text. For each provide: the passive sentence and an active voice alternative. Format as a numbered list.\n\nText:\n{$t}";
    },
    'active_convert' => function($t,$p) {
        if (!$t) return "Error: Paste your text in the Workbench first.";
        return "Rewrite the following text converting all passive voice sentences to active voice. Keep sentences that are already active unchanged. Return only the rewritten text.\n\nText:\n{$t}";
    },
    'clarity_check' => function($t,$p) {
        if (!$t) return "Error: Paste your text in the Workbench first.";
        return "Analyze the following text for clarity issues: vague language, wordiness, ambiguity, unnecessary jargon. Provide specific, actionable suggestions as a bulleted list.\n\nText:\n{$t}";
    },
    'readability_ai' => function($t,$p) {
        if (!$t) return "Error: Paste your text in the Workbench first.";
        return "Assess the readability of the following text. Cover: approximate reading grade level, sentence length variety, vocabulary difficulty, and structural clarity. Provide 3–5 specific suggestions to improve readability.\n\nText:\n{$t}";
    },

    // ═══════════════════════════ AI DETECTION ════════════════════════════════

    'ai_detect' => function($t,$p) {
        if (!$t) return "Error: Paste the text to analyze in the Workbench first.";
        return "Analyze whether the following text appears AI-generated. Consider: repetitive patterns, generic phrasing, lack of personal voice, overly perfect structure, unusual word choices. Provide:\n1. Verdict (Human / Likely AI / Definitely AI)\n2. Confidence (0–100%)\n3. Key indicators found\n4. Reasoning\n\nText:\n{$t}";
    },
    'gpt_detect' => function($t,$p) {
        if (!$t) return "Error: Paste the text to analyze in the Workbench first.";
        return "Analyze this text for GPT/large language model writing patterns. Look for: hedging language (\"certainly\", \"absolutely\"), over-explanation, perfect parallel structure, lack of authentic errors. Provide a verdict with specific patterns found.\n\nText:\n{$t}";
    },
    'chatgpt_detect' => function($t,$p) {
        if (!$t) return "Error: Paste the text to analyze in the Workbench first.";
        return "Analyze this text for ChatGPT-specific writing signatures: bullet-heavy structure, disclaimer phrases, overly balanced arguments, corporate-neutral tone, \"In conclusion\" or \"It's worth noting\" patterns. Provide a verdict with evidence.\n\nText:\n{$t}";
    },
    'ai_prob' => function($t,$p) {
        if (!$t) return "Error: Paste the text to analyze in the Workbench first.";
        return "Estimate the probability that the following text was AI-generated:\n- AI probability: X%\n- Human probability: X%\n- Confidence level (low/medium/high)\n- Top 3 reasons for your assessment\n\nText:\n{$t}";
    },
    'ai_score' => function($t,$p) {
        if (!$t) return "Error: Paste the text to analyze in the Workbench first.";
        return "Score the following text on AI likelihood from 0–100 (0=definitely human, 100=definitely AI). Provide:\n- AI Score: X/100\n- Factor breakdown: tone /10, structure /10, vocabulary /10, patterns /10\n- Overall assessment in 2–3 sentences\n\nText:\n{$t}";
    },

    // ═══════════════════════════ HUMANIZATION ════════════════════════════════

    'ai_humanize' => function($t,$p) {
        if (!$t) return "Error: Paste the text to humanize in the Workbench first.";
        return "Rewrite the following text to sound authentically human. Add personality, natural rhythm variation, contractions, and genuine voice. Remove all AI tells. Return only the rewritten text.\n\nText:\n{$t}";
    },
    'ai_rewriter' => function($t,$p) {
        if (!$t) return "Error: Paste the text to rewrite in the Workbench first.";
        return "Rewrite the following text to remove all AI-generated patterns. Make it sound like a real person wrote it spontaneously. Return only the rewritten text.\n\nText:\n{$t}";
    },
    'human_tone' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        return "Rewrite the following text in a casual, conversational human tone using contractions, everyday language, and natural rhythm. Return only the rewritten text.\n\nText:\n{$t}";
    },
    'natural_lang' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        return "Rewrite the following text using more natural, conversational language — how people actually speak. Replace stiff or formal phrasing. Return only the rewritten text.\n\nText:\n{$t}";
    },
    'undetectable' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        return "Completely rewrite the following text so it reads as authentically human-written. Vary sentence structure significantly, use natural imperfections, and ensure a consistent personal voice. Return only the rewritten text.\n\nText:\n{$t}";
    },

    // ═══════════════════════════ REWRITING ═══════════════════════════════════

    'paraphrase' => function($t,$p) {
        if (!$t) return "Error: Paste the text to paraphrase in the Workbench first.";
        return "Paraphrase the following text using different words and sentence structures while preserving the original meaning. Return only the paraphrased text.\n\nText:\n{$t}";
    },
    'sentence_rewrite' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        return "Rewrite each sentence using different words and structure while keeping the same meaning. Return only the rewritten text.\n\nText:\n{$t}";
    },
    'para_rewrite' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        return "Rewrite each paragraph with fresh wording and different phrasing while keeping the same meaning and structure. Return only the rewritten text.\n\nText:\n{$t}";
    },
    'article_rewrite' => function($t,$p) {
        if (!$t) return "Error: Paste the article in the Workbench first.";
        return "Fully rewrite the following article. Keep the same information and structure but use entirely different wording, phrasing, and examples. Return only the rewritten article.\n\nArticle:\n{$t}";
    },
    'content_improve' => function($t,$p) {
        if (!$t) return "Error: Paste the content in the Workbench first.";
        return "Improve the following content for quality, clarity, and impact. Fix weak sentences, add missing transitions, strengthen arguments. Return only the improved text.\n\nContent:\n{$t}";
    },
    'content_expand' => function($t,$p) {
        if (!$t) return "Error: Paste the content in the Workbench first.";
        return "Expand the following text with more detail, examples, and supporting points. Aim to roughly double the length while maintaining the original tone and meaning. Return only the expanded text.\n\nText:\n{$t}";
    },
    'content_shorten' => function($t,$p) {
        if (!$t) return "Error: Paste the content in the Workbench first.";
        return "Shorten the following text to its essential points. Remove redundancy, filler, and secondary details. Aim for about 50% of the original length. Return only the shortened text.\n\nText:\n{$t}";
    },

    // ═══════════════════════════ OPTIMIZATION ════════════════════════════════

    'clarity_improve' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        return "Rewrite the following text for maximum clarity. Eliminate vague language, passive voice, jargon, and long-winded sentences. Return only the improved text.\n\nText:\n{$t}";
    },
    'fluency_enhance' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        return "Enhance the flow and fluency of the following text. Improve transitions, vary sentence rhythm, and ensure smooth reading. Return only the enhanced text.\n\nText:\n{$t}";
    },
    'formality_adjust' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        $level = gp($p,'level','more formal');
        return "Rewrite the following text to sound {$level}. Adjust vocabulary, tone, and structure accordingly. Return only the rewritten text.\n\nText:\n{$t}";
    },
    'tone_convert' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        $tone = gp($p,'tone','professional');
        return "Rewrite the following text in a {$tone} tone. Adjust language, vocabulary, and style precisely. Return only the rewritten text.\n\nText:\n{$t}";
    },
    'simplify_conv' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        return "Rewrite the following text using simple, everyday language anyone can understand (aim for a 6th-grade level). Replace complex words, shorten sentences. Return only the simplified text.\n\nText:\n{$t}";
    },

    // ═══════════════════════════ ACADEMIC ════════════════════════════════════

    'plagiarism_check' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        return "Analyze the following text for internal repetition and self-plagiarism. Identify repeated ideas, duplicate sentences, and redundant passages. Rate the overall self-duplication level. Note: checks internal patterns only, not against external sources.\n\nText:\n{$t}";
    },
    'dup_content' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        return "Find all duplicate or near-duplicate sentences and phrases in the following text. List each duplicate with its occurrences. Rate overall duplication level (Low/Medium/High).\n\nText:\n{$t}";
    },
    'source_sim' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        return "Analyze the originality and uniqueness of the following text. Identify generic phrasing, clichés, and overly common expressions. Suggest specific ways to make it more original and distinctive.\n\nText:\n{$t}";
    },
    'cite_apa' => function($t,$p) {
        $src = gp($p,'source') ?: $t;
        if (!$src) return "Error: Enter source details in the Source field or paste in the Workbench.";
        return "Format the following source details as an APA 7th edition citation. Provide:\n1. In-text citation format\n2. Full reference list entry\n\nSource details:\n{$src}";
    },
    'cite_mla' => function($t,$p) {
        $src = gp($p,'source') ?: $t;
        if (!$src) return "Error: Enter source details in the Source field or paste in the Workbench.";
        return "Format the following source details as an MLA 9th edition citation. Provide:\n1. In-text citation format\n2. Works Cited entry\n\nSource details:\n{$src}";
    },
    'cite_chicago' => function($t,$p) {
        $src = gp($p,'source') ?: $t;
        if (!$src) return "Error: Enter source details in the Source field or paste in the Workbench.";
        return "Format the following source details as a Chicago 17th edition citation. Provide:\n1. Footnote/endnote format\n2. Bibliography entry\n\nSource details:\n{$src}";
    },
    'cite_harvard' => function($t,$p) {
        $src = gp($p,'source') ?: $t;
        if (!$src) return "Error: Enter source details in the Source field or paste in the Workbench.";
        return "Format the following source details as a Harvard citation. Provide:\n1. In-text citation format\n2. Reference list entry\n\nSource details:\n{$src}";
    },
    'cite_ieee' => function($t,$p) {
        $src = gp($p,'source') ?: $t;
        if (!$src) return "Error: Enter source details in the Source field or paste in the Workbench.";
        return "Format the following source details as an IEEE citation (numbered reference style used in IEEE publications). Provide the complete formatted reference.\n\nSource details:\n{$src}";
    },
    'essay_gen' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        $len = gp($p,'length','500 words');
        return "Write a well-structured academic essay on: \"{$topic}\". Include introduction with thesis statement, 3 body paragraphs with supporting arguments, and conclusion. Target approximately {$len}.";
    },
    'thesis_gen' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        return "Generate 5 strong thesis statement options for: \"{$topic}\". Each should make a clear, arguable, specific claim. Output as a numbered list only.";
    },
    'research_q' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        return "Generate 8 research questions for: \"{$topic}\". Mix descriptive, comparative, causal, and evaluative questions. Output as a numbered list only.";
    },
    'abstract_gen' => function($t,$p) {
        if (!$t) return "Error: Paste your paper/content in the Workbench first.";
        return "Write a 150–250 word academic abstract for the following content. Include: background/context, objective, key findings/arguments, and conclusion.\n\nContent:\n{$t}";
    },
    'lit_review' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        return "Write a literature review section for: \"{$topic}\". Discuss the state of existing knowledge, key themes, debates, gaps in research, and implications for further study. Use academic tone. Aim for 300–400 words.";
    },

    // ═══════════════════════════ LANGUAGE ════════════════════════════════════

    'translate' => function($t,$p) {
        if (!$t) return "Error: Paste the text to translate in the Workbench first.";
        $lang = gp($p,'language','Spanish');
        return "Translate the following text to {$lang}. Preserve tone, style, and meaning exactly. Return only the translated text.\n\nText:\n{$t}";
    },
    'text_localize' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        $region = gp($p,'region','British English');
        return "Localize the following text for {$region}. Adapt idioms, spelling, units, cultural references, and expressions appropriately. Return only the localized text.\n\nText:\n{$t}";
    },
    'lang_detect' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        return "Detect the language of the following text. Provide:\n1. Primary language and dialect/variant\n2. Confidence level\n3. Any mixed languages or code-switching detected\n4. Notable linguistic features\n\nText:\n{$t}";
    },
    'synonym_find' => function($t,$p) {
        $word = gp($p,'word') ?: strtok($t, " \n\t");
        if (!$word) return "Error: Enter a word in the Word field.";
        return "Find 15 synonyms for \"{$word}\". Group them by nuance (formal, informal, strong, weak, positive, negative). Include a one-line definition of the original word at the top.";
    },
    'antonym_find' => function($t,$p) {
        $word = gp($p,'word') ?: strtok($t, " \n\t");
        if (!$word) return "Error: Enter a word in the Word field.";
        return "Find 10 antonyms for \"{$word}\". Include context for when each antonym is most appropriate. Include a one-line definition of the original word at the top.";
    },
    'rhyme_find' => function($t,$p) {
        $word = gp($p,'word') ?: strtok($t, " \n\t");
        if (!$word) return "Error: Enter a word in the Word field.";
        return "Find 20 words that rhyme with \"{$word}\". Group as: perfect rhymes, near rhymes, and end rhymes. Output as a structured list.";
    },
    'dict_lookup' => function($t,$p) {
        $word = gp($p,'word') ?: strtok($t, " \n\t");
        if (!$word) return "Error: Enter a word in the Word field.";
        return "Provide a comprehensive dictionary entry for \"{$word}\":\n1. Pronunciation (phonetic)\n2. Part(s) of speech\n3. All definitions (numbered)\n4. Etymology (origin)\n5. Example sentences (2–3)\n6. Common collocations";
    },
    'phrase_meaning' => function($t,$p) {
        $phrase = gp($p,'phrase') ?: $t;
        if (!$phrase) return "Error: Enter a phrase in the Phrase field or paste in the Workbench.";
        return "Explain the meaning of: \"{$phrase}\"\n- Literal vs figurative meaning\n- Origin/etymology (if known)\n- When/how it's used\n- Example in a sentence";
    },
    'idiom_find' => function($t,$p) {
        $concept = gp($p,'concept') ?: $t;
        if (!$concept) return "Error: Enter a concept in the Concept field or paste in the Workbench.";
        return "Find 10 idioms and expressions related to the concept of \"{$concept}\". For each provide the idiom and its meaning. Output as a numbered list.";
    },
    'vocab_enhance' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        return "Rewrite the following text replacing common, overused words with more precise and sophisticated vocabulary. Maintain the original meaning and tone. Return only the enhanced text.\n\nText:\n{$t}";
    },
    'simplify_vocab' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        return "Rewrite the following text replacing complex and advanced words with simpler, more commonly used alternatives. Make it accessible to everyone. Return only the simplified text.\n\nText:\n{$t}";
    },
    'adv_vocab' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        return "Identify all advanced or sophisticated vocabulary in the following text. For each word provide: the word, its meaning in context, and the sentence it appears in. Format as a glossary list.\n\nText:\n{$t}";
    },
    'common_replace' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        return "Rewrite the following text replacing all advanced, technical, or uncommon words with the most common everyday alternatives. Return only the simplified text.\n\nText:\n{$t}";
    },

    // ═══════════════════════════ SEO ═════════════════════════════════════════

    'kw_insert' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        $kw = gp($p,'keyword');
        if (!$kw) return "Error: Enter a keyword in the Keyword field.";
        return "Naturally insert the keyword \"{$kw}\" into the following text 2–3 times. Place it where it fits organically without keyword stuffing. Return only the modified text.\n\nText:\n{$t}";
    },
    'kw_optimize' => function($t,$p) {
        if (!$t) return "Error: Paste the text in the Workbench first.";
        $kw = gp($p,'keyword');
        if (!$kw) return "Error: Enter a keyword in the Keyword field.";
        return "Optimize the following text for the keyword \"{$kw}\". Ensure natural density (1–2%), add keyword variations and LSI terms naturally. Return only the optimized text.\n\nText:\n{$t}";
    },
    'seo_title' => function($t,$p) {
        $kw = gp($p,'keyword') ?: $t;
        if (!$kw) return "Error: Enter a keyword or paste content in the Workbench.";
        return "Generate 5 SEO-optimized page title tags for: \"{$kw}\". Each should be 50–60 characters, include the keyword near the front, and be compelling for clicks. Output as a numbered list with character counts in parentheses.";
    },
    'seo_meta' => function($t,$p) {
        $kw = gp($p,'keyword');
        $kwNote = $kw ? " (target keyword: \"{$kw}\")" : '';
        if (!$t) return "Error: Paste the page content in the Workbench first.";
        return "Write 3 SEO meta descriptions{$kwNote} for the following content. Each should be 140–160 characters, include a benefit and a call to action. Output as a numbered list with character counts.\n\nContent:\n{$t}";
    },
    'snippet_opt' => function($t,$p) {
        if (!$t) return "Error: Paste the content to optimize in the Workbench first.";
        return "Optimize the following text for Google Featured Snippets. Restructure into clear question-and-answer format, use numbered steps or bullet lists where appropriate, and ensure direct answers appear prominently. Return the optimized version.\n\nText:\n{$t}";
    },
    'content_gap' => function($t,$p) {
        if (!$t) return "Error: Paste the content to analyze in the Workbench first.";
        return "Analyze the following content for content gaps. Identify:\n1. Topics a reader would expect but are missing\n2. Unanswered questions\n3. Sections that need more depth\n4. Related topics to add for comprehensiveness\n\nContent:\n{$t}";
    },
    'semantic_kw' => function($t,$p) {
        $kw = gp($p,'keyword') ?: $t;
        if (!$kw) return "Error: Enter a main keyword or paste content in the Workbench.";
        return "Generate 20 semantically related keywords and LSI terms for \"{$kw}\". Group by: related topics, long-tail variations, questions people ask, and related entities/concepts. Output as a structured list.";
    },

    // ═══════════════════════════ SOCIAL MEDIA ════════════════════════════════

    'yt_vid_title' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        return "Generate 5 YouTube video title options for: \"{$topic}\". Make them clickable, SEO-friendly, and algorithm-optimized. Mix styles: how-to, numbers, curiosity, strong emotion. Output as a numbered list only.";
    },
    'yt_vid_desc' => function($t,$p) {
        if (!$t) return "Error: Paste your video content/topic in the Workbench first.";
        return "Write a YouTube video description for the following content. Include: hook (2 sentences), content summary, what viewers will learn (bullet points), subscribe CTA, [TIMESTAMPS] placeholder section, and 3–5 relevant hashtags.\n\nContent:\n{$t}";
    },
    'yt_tags_gen' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        return "Generate 20 YouTube tags for: \"{$topic}\". Mix broad terms and specific long-tail tags. Output as a comma-separated list on one line.";
    },
    'ig_caption' => function($t,$p) {
        if (!$t) return "Error: Paste your content/topic in the Workbench first.";
        $tone = gp($p,'tone','engaging');
        return "Write an Instagram caption in a {$tone} tone for the following content. Include: a hook line, story/value, call to action, and 10–15 relevant hashtags at the end.\n\nContent:\n{$t}";
    },
    'ig_bio' => function($t,$p) {
        $niche = gp($p,'niche') ?: $t;
        if (!$niche) return "Error: Enter your niche/brand in the Niche field or paste in the Workbench.";
        return "Write 3 Instagram bio options for: \"{$niche}\". Each must be under 150 characters and include what you do, who you help, and a CTA. Output as a numbered list.";
    },
    'ig_hashtags' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        return "Generate 30 Instagram hashtags for: \"{$topic}\". Mix: 5 broad (1M+ posts), 10 medium (100K–1M), 10 niche (10K–100K), 5 micro (<10K). Output as space-separated hashtags.";
    },
    'li_post' => function($t,$p) {
        if (!$t) return "Error: Paste your content/topic in the Workbench first.";
        return "Write a LinkedIn post for the following content. Use professional but engaging tone. Start with a strong hook, share insight or brief story, end with a thought-provoking question. Under 300 words.\n\nContent:\n{$t}";
    },
    'li_headline' => function($t,$p) {
        $role = gp($p,'role') ?: $t;
        if (!$role) return "Error: Enter your role/expertise in the Role field or paste in the Workbench.";
        return "Write 5 LinkedIn profile headline options for: \"{$role}\". Each under 120 characters, communicating value not just job title. Output as a numbered list only.";
    },
    'li_summary' => function($t,$p) {
        if (!$t) return "Error: Paste your professional background in the Workbench first.";
        return "Write a LinkedIn About/Summary section for the following professional background. Include: who you are, what you do, unique value, key achievements, and what you're looking for. 200–300 words.\n\nBackground:\n{$t}";
    },
    'tweet_gen' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        return "Write a tweet about: \"{$topic}\". Make it engaging, under 280 characters, with 1–2 relevant hashtags. Output only the tweet text.";
    },
    'thread_gen' => function($t,$p) {
        $topic = gp($p,'topic') ?: $t;
        if (!$topic) return "Error: Enter a topic or paste content in the Workbench.";
        return "Write a 5-tweet Twitter/X thread about: \"{$topic}\". Number each tweet (1/5, 2/5, etc.). Start with a hook tweet, end with a CTA. Each tweet under 280 characters.";
    },
    'tweet_rewrite' => function($t,$p) {
        if (!$t) return "Error: Paste the tweet to rewrite in the Workbench first.";
        return "Rewrite the following tweet to be more engaging, shareable, and impactful. Keep it under 280 characters. Output only the rewritten tweet.\n\nTweet:\n{$t}";
    },

    // ═══════════════════════════ GENERATORS ══════════════════════════════════

    'rand_word' => function($t,$p) {
        $cat = gp($p,'category','any category');
        return "Generate 10 interesting, varied random words" . ($cat !== 'any category' ? " related to {$cat}" : "") . ". Mix different parts of speech. For each include the part of speech and a brief definition. Output as a numbered list.";
    },
    'rand_sentence' => function($t,$p) {
        $theme = gp($p,'theme','');
        return "Generate 5 random, creative sentences" . ($theme ? " with a {$theme} theme" : "") . ". Vary their structure and style. Output as a numbered list only.";
    },
    'rand_para' => function($t,$p) {
        $topic = gp($p,'topic','');
        return "Generate one random, interesting paragraph" . ($topic ? " about {$topic}" : " on any topic of your choice") . ". Make it engaging, well-written, and about 80–100 words. Output only the paragraph.";
    },
    'rand_quote' => function($t,$p) {
        $theme = gp($p,'theme','wisdom and perseverance');
        return "Generate one original, thought-provoking quote about {$theme}. It should feel genuine and memorable, not clichéd. Output only the quote in quotation marks.";
    },
    'rand_name' => function($t,$p) {
        $origin = gp($p,'origin','');
        return "Generate 10 random full names" . ($origin ? " of {$origin} origin" : " from diverse cultural backgrounds") . ". Mix male, female, and gender-neutral names. Output as a numbered list only.";
    },
    'username_gen' => function($t,$p) {
        $niche = gp($p,'niche') ?: ($t ?: 'general');
        return "Generate 10 creative, catchy usernames for a {$niche} account. Mix styles: simple, wordplay, abbreviations, combined words. Output as a numbered list only.";
    },
    'nickname_gen' => function($t,$p) {
        $name = gp($p,'name') ?: ($t ?: 'Alex');
        return "Generate 10 creative nicknames for the name \"{$name}\". Mix cute, cool, funny, and professional styles. Note the vibe/style for each. Output as a numbered list.";
    },
    'pen_name_gen' => function($t,$p) {
        $genre = gp($p,'genre','general fiction');
        return "Generate 10 pen name ideas for a {$genre} author. Make them memorable, easy to pronounce, and genre-appropriate. Output as a numbered list only.";
    },
    'fake_company' => function($t,$p) {
        $industry = gp($p,'industry') ?: ($t ?: 'technology');
        return "Generate 10 fake but realistic-sounding company names for the {$industry} industry. Vary styles: descriptive, abstract, acronym, portmanteau. Output as a numbered list only.";
    },
];

/* ─── Dispatch ───────────────────────────────────────────────────────────── */
if (!isset($tools[$tool])) {
    echo json_encode(['error' => "Unknown tool: {$tool}"]); exit;
}

$prompt = $tools[$tool]($text, $params);

// Return prompt-level errors directly (tools return "Error: ..." strings)
if (str_starts_with($prompt, 'Error:')) {
    echo json_encode(['error' => substr($prompt, 7)]); exit;
}

/* ─── Gemini 2.0 Flash call ──────────────────────────────────────────────── */
if (!function_exists('curl_init')) {
    echo json_encode(['error' => 'cURL is not available on this server']); exit;
}

$url     = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . urlencode($apiKey);
$payload = json_encode([
    'contents'         => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => ['temperature' => 0.75, 'maxOutputTokens' => 2048, 'topP' => 0.95],
    'safetySettings'   => [
        ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_NONE'],
        ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_NONE'],
        ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
        ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
    ],
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => 0,
]);
$response = curl_exec($ch);
$errno    = curl_errno($ch);
$errmsg   = curl_error($ch);
curl_close($ch);

if ($errno || $response === false) {
    echo json_encode(['error' => 'Request failed: ' . ($errmsg ?: 'cURL error')]); exit;
}

$data = json_decode($response, true);

if (!empty($data['error'])) {
    echo json_encode(['error' => $data['error']['message'] ?? 'Gemini API error']); exit;
}

$result = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
if (!$result) {
    $reason = $data['candidates'][0]['finishReason'] ?? 'unknown';
    echo json_encode(['error' => "No response generated (reason: {$reason})"]); exit;
}

echo json_encode(['result' => trim($result)]);
