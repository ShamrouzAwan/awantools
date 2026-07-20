/**
 * Text Toolkit — AI Tools extension (Gemini-powered)
 * Appended after text-toolkit.js; extends window.XT.
 */
(function () {
  'use strict';

  var $ = function (sel) { return document.querySelector(sel); };

  /* ── AI Tool Definitions ─────────────────────────────────────────────── */
  var AI_TOOLS = [

    // ── WRITING ─────────────────────────────────────────────────────────
    { tab:'writing', section:'Headlines', id:'headline',     label:'Headline Generator',        desc:'Generate 5 click-worthy headlines for any topic or content.',       needsText:false, params:[{key:'topic',label:'Topic',type:'text',ph:'e.g. remote work productivity tips',req:true}] },
    { tab:'writing', section:'Headlines', id:'blog_title',   label:'Blog Title Generator',      desc:'Create 5 SEO-friendly blog post title ideas.',                      needsText:false, params:[{key:'topic',label:'Topic',type:'text',ph:'e.g. beginner\'s guide to investing',req:true}] },
    { tab:'writing', section:'Headlines', id:'article_title',label:'Article Title Generator',   desc:'Generate 5 professional article title options.',                    needsText:false, params:[{key:'topic',label:'Topic',type:'text',ph:'e.g. future of renewable energy',req:true}] },
    { tab:'writing', section:'Headlines', id:'yt_title',     label:'YouTube Title Generator',   desc:'Create 5 algorithm-optimized YouTube video titles.',                needsText:false, params:[{key:'topic',label:'Topic',type:'text',ph:'e.g. how to make sourdough bread',req:true}] },
    { tab:'writing', section:'Headlines', id:'email_subject',label:'Email Subject Generator',   desc:'Write 5 email subject lines that get opened.',                      needsText:false, params:[{key:'topic',label:'Topic',type:'text',ph:'e.g. product launch announcement',req:true}] },
    { tab:'writing', section:'Headlines', id:'ad_headline',  label:'Ad Headline Generator',     desc:'Write 5 Google Ads headlines (≤30 chars each).',                    needsText:false, params:[{key:'topic',label:'Product/topic',type:'text',ph:'e.g. project management software',req:true}] },

    { tab:'writing', section:'Content',   id:'intro_gen',    label:'Introduction Generator',    desc:'Write a strong opening paragraph for your content.',                needsText:true,  params:[] },
    { tab:'writing', section:'Content',   id:'conclusion_gen',label:'Conclusion Generator',     desc:'Write a compelling closing paragraph for your content.',            needsText:true,  params:[] },
    { tab:'writing', section:'Content',   id:'outline_gen',  label:'Outline Generator',         desc:'Build a detailed H1/H2/H3 content outline.',                       needsText:false, params:[{key:'topic',label:'Topic',type:'text',ph:'e.g. complete guide to SEO',req:false}] },
    { tab:'writing', section:'Content',   id:'para_gen',     label:'Paragraph Generator',       desc:'Generate a polished paragraph on any topic.',                      needsText:false, params:[{key:'topic',label:'Topic',type:'text',ph:'e.g. benefits of daily exercise',req:true}] },
    { tab:'writing', section:'Content',   id:'hook_gen',     label:'Hook Generator',            desc:'Write 5 attention-grabbing opening hooks.',                        needsText:false, params:[{key:'topic',label:'Topic',type:'text',ph:'e.g. why most diets fail',req:false}] },
    { tab:'writing', section:'Content',   id:'cta_gen',      label:'CTA Generator',             desc:'Generate 5 compelling call-to-action phrases.',                    needsText:false, params:[{key:'action',label:'Desired action',type:'text',ph:'e.g. sign up, download, buy now',req:false}] },
    { tab:'writing', section:'Content',   id:'summary_gen',  label:'Summary Generator',         desc:'Summarize your content in 2–3 sentences.',                         needsText:true,  params:[] },

    { tab:'writing', section:'Marketing', id:'product_desc', label:'Product Description',       desc:'Write a conversion-focused product description.',                  needsText:false, params:[{key:'product',label:'Product/service',type:'text',ph:'e.g. ergonomic standing desk',req:false}] },
    { tab:'writing', section:'Marketing', id:'sales_copy',   label:'Sales Copy Generator',      desc:'Write AIDA-structured persuasive sales copy.',                     needsText:true,  params:[] },
    { tab:'writing', section:'Marketing', id:'landing_copy', label:'Landing Page Copy',         desc:'Generate hero headline, subheadline, bullets, and CTA.',           needsText:true,  params:[] },
    { tab:'writing', section:'Marketing', id:'ad_copy',      label:'Ad Copy Generator',         desc:'Write platform ad copy with headline, body, and CTA.',             needsText:true,  params:[{key:'platform',label:'Platform',type:'text',ph:'Facebook, Instagram, Google…',req:false}] },
    { tab:'writing', section:'Marketing', id:'fb_ad',        label:'Facebook Ad Generator',     desc:'Write primary text, headline, and description for a Facebook ad.',  needsText:true,  params:[] },
    { tab:'writing', section:'Marketing', id:'google_ad',    label:'Google Ad Generator',       desc:'Write 3 RSA variations with headlines and descriptions.',           needsText:true,  params:[] },

    { tab:'writing', section:'Business',  id:'biz_name',     label:'Business Name Generator',   desc:'Generate 10 unique business name ideas for your industry.',        needsText:false, params:[{key:'industry',label:'Industry/concept',type:'text',ph:'e.g. eco-friendly cleaning products',req:true}] },
    { tab:'writing', section:'Business',  id:'brand_name',   label:'Brand Name Generator',      desc:'Generate 10 short, memorable brand names.',                        needsText:false, params:[{key:'concept',label:'Concept',type:'text',ph:'e.g. fast, healthy meal delivery',req:true}] },
    { tab:'writing', section:'Business',  id:'slogan',       label:'Slogan Generator',          desc:'Write 5 catchy slogans for your brand.',                           needsText:false, params:[{key:'brand',label:'Brand/product',type:'text',ph:'e.g. FitFuel protein shakes',req:false}] },
    { tab:'writing', section:'Business',  id:'tagline',      label:'Tagline Generator',         desc:'Generate 5 evocative brand taglines.',                             needsText:false, params:[{key:'brand',label:'Brand/product',type:'text',ph:'e.g. CloudSync backup tool',req:false}] },
    { tab:'writing', section:'Business',  id:'mission_stmt', label:'Mission Statement',         desc:'Write a clear, inspiring mission statement.',                      needsText:false, params:[{key:'company',label:'Company/context',type:'text',ph:'e.g. online tutoring platform for K-12',req:false}] },
    { tab:'writing', section:'Business',  id:'vision_stmt',  label:'Vision Statement',          desc:'Write an ambitious vision statement for your organisation.',        needsText:false, params:[{key:'company',label:'Company/context',type:'text',ph:'e.g. sustainable fashion marketplace',req:false}] },
    { tab:'writing', section:'Business',  id:'elevator_pitch',label:'Elevator Pitch',           desc:'Write a 30-second pitch (under 80 words).',                        needsText:true,  params:[] },

    // ── GRAMMAR ─────────────────────────────────────────────────────────
    { tab:'aitools', section:'Grammar',      id:'grammar_check',   label:'Grammar Checker',         desc:'Find and explain every grammar error in your text.',       needsText:true, params:[] },
    { tab:'aitools', section:'Grammar',      id:'spelling_check',  label:'Spelling Checker',        desc:'Detect all spelling mistakes with correct alternatives.',  needsText:true, params:[] },
    { tab:'aitools', section:'Grammar',      id:'punct_check',     label:'Punctuation Checker',     desc:'Spot punctuation errors and inconsistencies.',             needsText:true, params:[] },
    { tab:'aitools', section:'Grammar',      id:'cap_check',       label:'Capitalization Checker',  desc:'Find capitalization issues in your text.',                 needsText:true, params:[] },
    { tab:'aitools', section:'Grammar',      id:'sentence_correct',label:'Sentence Corrector',      desc:'Auto-fix all grammar, spelling, and punctuation errors.',  needsText:true, params:[] },
    { tab:'aitools', section:'Grammar',      id:'passive_check',   label:'Passive Voice Checker',   desc:'Identify passive voice sentences with active alternatives.', needsText:true, params:[] },
    { tab:'aitools', section:'Grammar',      id:'active_convert',  label:'Active Voice Converter',  desc:'Rewrite all passive voice sentences as active voice.',     needsText:true, params:[] },
    { tab:'aitools', section:'Grammar',      id:'clarity_check',   label:'Writing Clarity Checker', desc:'Find vague, wordy, and ambiguous writing.',                needsText:true, params:[] },
    { tab:'aitools', section:'Grammar',      id:'readability_ai',  label:'Readability Checker',     desc:'Assess reading level and suggest improvements.',           needsText:true, params:[] },

    // ── AI DETECTION ──────────────────────────────────────────────────
    { tab:'aitools', section:'AI Detection', id:'ai_detect',    label:'AI Content Detector',     desc:'Determine if text is AI-generated with reasoning.',         needsText:true, params:[] },
    { tab:'aitools', section:'AI Detection', id:'gpt_detect',   label:'GPT Content Detector',    desc:'Detect GPT-specific writing patterns and tells.',          needsText:true, params:[] },
    { tab:'aitools', section:'AI Detection', id:'chatgpt_detect',label:'ChatGPT Detector',       desc:'Check for ChatGPT-specific signatures and structures.',     needsText:true, params:[] },
    { tab:'aitools', section:'AI Detection', id:'ai_prob',      label:'AI Probability Checker',  desc:'Estimate the probability (%) that text is AI-written.',    needsText:true, params:[] },
    { tab:'aitools', section:'AI Detection', id:'ai_score',     label:'AI Score Calculator',     desc:'Score AI likelihood 0–100 with a factor breakdown.',       needsText:true, params:[] },

    // ── HUMANIZATION ──────────────────────────────────────────────────
    { tab:'aitools', section:'Humanization', id:'ai_humanize',  label:'AI Humanizer',            desc:'Rewrite AI text to sound authentically human.',            needsText:true, params:[] },
    { tab:'aitools', section:'Humanization', id:'ai_rewriter',  label:'AI Text Rewriter',        desc:'Remove all AI patterns and rewrite as a real person.',     needsText:true, params:[] },
    { tab:'aitools', section:'Humanization', id:'human_tone',   label:'Human Tone Converter',    desc:'Rewrite in casual, natural, conversational language.',     needsText:true, params:[] },
    { tab:'aitools', section:'Humanization', id:'natural_lang', label:'Natural Language Converter',desc:'Replace stiff phrasing with how people actually speak.',  needsText:true, params:[] },
    { tab:'aitools', section:'Humanization', id:'undetectable', label:'Undetectable AI Rewriter',desc:'Completely rewrite to pass as human-written.',             needsText:true, params:[] },

    // ── REWRITING ─────────────────────────────────────────────────────
    { tab:'aitools', section:'Rewriting',    id:'paraphrase',       label:'Paraphrasing Tool',       desc:'Paraphrase while preserving the original meaning.',        needsText:true, params:[] },
    { tab:'aitools', section:'Rewriting',    id:'sentence_rewrite', label:'Sentence Rewriter',       desc:'Rewrite each sentence with different words.',             needsText:true, params:[] },
    { tab:'aitools', section:'Rewriting',    id:'para_rewrite',     label:'Paragraph Rewriter',      desc:'Rewrite each paragraph with fresh phrasing.',             needsText:true, params:[] },
    { tab:'aitools', section:'Rewriting',    id:'article_rewrite',  label:'Article Rewriter',        desc:'Fully rewrite the article with entirely new wording.',    needsText:true, params:[] },
    { tab:'aitools', section:'Rewriting',    id:'content_improve',  label:'Content Improver',        desc:'Fix weak sentences and improve quality and flow.',        needsText:true, params:[] },
    { tab:'aitools', section:'Rewriting',    id:'content_expand',   label:'Content Expander',        desc:'Expand text with more detail and examples (~2× length).',  needsText:true, params:[] },
    { tab:'aitools', section:'Rewriting',    id:'content_shorten',  label:'Content Shortener',       desc:'Condense to key points only (~50% of original).',         needsText:true, params:[] },

    // ── OPTIMIZATION ──────────────────────────────────────────────────
    { tab:'aitools', section:'Optimization', id:'clarity_improve',  label:'Clarity Improver',        desc:'Eliminate vague language, jargon, and passive voice.',    needsText:true, params:[] },
    { tab:'aitools', section:'Optimization', id:'fluency_enhance',  label:'Fluency Enhancer',        desc:'Improve flow, transitions, and sentence variety.',        needsText:true, params:[] },
    { tab:'aitools', section:'Optimization', id:'formality_adjust', label:'Formality Adjuster',      desc:'Adjust how formal or informal the text sounds.',          needsText:true, params:[{key:'level',label:'Direction',type:'select',opts:['more formal','more casual','neutral'],req:false}] },
    { tab:'aitools', section:'Optimization', id:'tone_convert',     label:'Tone Converter',          desc:'Rewrite in a specific tone.',                             needsText:true, params:[{key:'tone',label:'Target tone',type:'select',opts:['professional','casual','friendly','authoritative','empathetic','persuasive'],req:false}] },
    { tab:'aitools', section:'Optimization', id:'simplify_conv',    label:'Simplicity Converter',    desc:'Simplify language to a 6th-grade reading level.',         needsText:true, params:[] },

    // ── ACADEMIC ────────────────────────────────────────────────────
    { tab:'academic', section:'Research',    id:'plagiarism_check',label:'Plagiarism Checker',      desc:'Detect internal repetition and self-plagiarism patterns.',  needsText:true,  params:[] },
    { tab:'academic', section:'Research',    id:'dup_content',     label:'Duplicate Content Checker',desc:'Find duplicate and near-duplicate sentences.',             needsText:true,  params:[] },
    { tab:'academic', section:'Research',    id:'source_sim',      label:'Source Similarity Checker',desc:'Assess originality and flag overused expressions.',        needsText:true,  params:[] },

    { tab:'academic', section:'Citations',   id:'cite_apa',        label:'APA Citation Generator',  desc:'Format a source in APA 7th edition.',                      needsText:false, params:[{key:'source',label:'Source details',type:'textarea',ph:'Author, title, year, publisher, URL…',req:false}] },
    { tab:'academic', section:'Citations',   id:'cite_mla',        label:'MLA Citation Generator',  desc:'Format a source in MLA 9th edition.',                      needsText:false, params:[{key:'source',label:'Source details',type:'textarea',ph:'Author, title, year, publisher, URL…',req:false}] },
    { tab:'academic', section:'Citations',   id:'cite_chicago',    label:'Chicago Citation',         desc:'Format a source in Chicago 17th edition.',                 needsText:false, params:[{key:'source',label:'Source details',type:'textarea',ph:'Author, title, year, publisher, URL…',req:false}] },
    { tab:'academic', section:'Citations',   id:'cite_harvard',    label:'Harvard Citation Generator',desc:'Format a source in Harvard style.',                       needsText:false, params:[{key:'source',label:'Source details',type:'textarea',ph:'Author, title, year, publisher, URL…',req:false}] },
    { tab:'academic', section:'Citations',   id:'cite_ieee',       label:'IEEE Citation Generator',  desc:'Format a source in IEEE numbered reference style.',        needsText:false, params:[{key:'source',label:'Source details',type:'textarea',ph:'Author, title, year, publication…',req:false}] },

    { tab:'academic', section:'Student Tools', id:'essay_gen',     label:'Essay Generator',          desc:'Write a full academic essay with intro, body, and conclusion.', needsText:false, params:[{key:'topic',label:'Essay topic',type:'text',ph:'e.g. impact of social media on democracy',req:true},{key:'length',label:'Target length',type:'select',opts:['300 words','500 words','800 words','1000 words'],req:false}] },
    { tab:'academic', section:'Student Tools', id:'thesis_gen',    label:'Thesis Statement Generator',desc:'Generate 5 arguable thesis statement options.',             needsText:false, params:[{key:'topic',label:'Topic',type:'text',ph:'e.g. climate change policies',req:true}] },
    { tab:'academic', section:'Student Tools', id:'research_q',    label:'Research Question Generator',desc:'Generate 8 research questions across question types.',    needsText:false, params:[{key:'topic',label:'Topic',type:'text',ph:'e.g. effects of screen time on children',req:true}] },
    { tab:'academic', section:'Student Tools', id:'abstract_gen',  label:'Abstract Generator',       desc:'Write a 150–250 word academic abstract.',                   needsText:true,  params:[] },
    { tab:'academic', section:'Student Tools', id:'lit_review',    label:'Literature Review Generator',desc:'Write a literature review section for a topic.',          needsText:false, params:[{key:'topic',label:'Topic',type:'text',ph:'e.g. machine learning in healthcare',req:true}] },

    { tab:'academic', section:'Translation', id:'translate',       label:'Language Translator',      desc:'Translate text to any target language.',                   needsText:true,  params:[{key:'language',label:'Target language',type:'text',ph:'e.g. Spanish, French, Japanese',req:true}] },
    { tab:'academic', section:'Translation', id:'text_localize',   label:'Text Localizer',           desc:'Adapt text for a specific region or cultural context.',    needsText:true,  params:[{key:'region',label:'Target region',type:'text',ph:'e.g. British English, Australian',req:true}] },
    { tab:'academic', section:'Translation', id:'lang_detect',     label:'Language Detector',        desc:'Identify the language, dialect, and mixed languages.',     needsText:true,  params:[] },

    { tab:'academic', section:'Linguistics', id:'synonym_find',    label:'Synonym Finder',           desc:'Find 15 synonyms grouped by nuance.',                      needsText:false, params:[{key:'word',label:'Word',type:'text',ph:'e.g. happy',req:true}] },
    { tab:'academic', section:'Linguistics', id:'antonym_find',    label:'Antonym Finder',           desc:'Find 10 antonyms with usage context.',                     needsText:false, params:[{key:'word',label:'Word',type:'text',ph:'e.g. fast',req:true}] },
    { tab:'academic', section:'Linguistics', id:'rhyme_find',      label:'Rhyming Words Finder',     desc:'Find 20 perfect and near-rhymes.',                         needsText:false, params:[{key:'word',label:'Word',type:'text',ph:'e.g. night',req:true}] },
    { tab:'academic', section:'Linguistics', id:'dict_lookup',     label:'Dictionary Lookup',        desc:'Get pronunciation, definitions, etymology, and examples.',  needsText:false, params:[{key:'word',label:'Word',type:'text',ph:'e.g. ephemeral',req:true}] },
    { tab:'academic', section:'Linguistics', id:'phrase_meaning',  label:'Phrase Meaning Finder',    desc:'Explain literal/figurative meaning and origin.',           needsText:false, params:[{key:'phrase',label:'Phrase or idiom',type:'text',ph:'e.g. bite the bullet',req:false}] },
    { tab:'academic', section:'Linguistics', id:'idiom_find',      label:'Idiom Finder',             desc:'Find 10 idioms related to a concept.',                     needsText:false, params:[{key:'concept',label:'Concept',type:'text',ph:'e.g. time, failure, courage',req:true}] },

    { tab:'academic', section:'Vocabulary', id:'vocab_enhance',    label:'Vocabulary Enhancer',      desc:'Replace common words with more sophisticated alternatives.', needsText:true, params:[] },
    { tab:'academic', section:'Vocabulary', id:'simplify_vocab',   label:'Simplify Text',            desc:'Replace complex words with simple, everyday alternatives.', needsText:true, params:[] },
    { tab:'academic', section:'Vocabulary', id:'adv_vocab',        label:'Advanced Vocabulary Generator',desc:'Identify and explain all advanced vocabulary used.',    needsText:true, params:[] },
    { tab:'academic', section:'Vocabulary', id:'common_replace',   label:'Common Words Replacer',    desc:'Swap advanced words for their most common equivalents.',   needsText:true, params:[] },

    // ── SEO ─────────────────────────────────────────────────────────
    { tab:'seosocial', section:'SEO',       id:'kw_insert',     label:'Keyword Inserter',          desc:'Naturally insert a keyword 2–3 times into your text.',       needsText:true,  params:[{key:'keyword',label:'Keyword',type:'text',ph:'e.g. best project management tools',req:true}] },
    { tab:'seosocial', section:'SEO',       id:'kw_optimize',   label:'Keyword Density Optimizer', desc:'Optimize keyword density to 1–2% without stuffing.',         needsText:true,  params:[{key:'keyword',label:'Keyword',type:'text',ph:'e.g. cloud storage solutions',req:true}] },
    { tab:'seosocial', section:'SEO',       id:'seo_title',     label:'SEO Title Generator',       desc:'Generate 5 title tags (50–60 chars) for a keyword.',         needsText:false, params:[{key:'keyword',label:'Keyword / topic',type:'text',ph:'e.g. best running shoes for beginners',req:true}] },
    { tab:'seosocial', section:'SEO',       id:'seo_meta',      label:'SEO Meta Description',      desc:'Write 3 meta descriptions (140–160 chars) with CTAs.',       needsText:true,  params:[{key:'keyword',label:'Target keyword (optional)',type:'text',ph:'e.g. email marketing software',req:false}] },
    { tab:'seosocial', section:'SEO',       id:'snippet_opt',   label:'Featured Snippet Optimizer',desc:'Restructure content for Google Featured Snippets.',          needsText:true,  params:[] },
    { tab:'seosocial', section:'SEO',       id:'content_gap',   label:'Content Gap Analyzer',      desc:'Identify missing topics and gaps in your content.',          needsText:true,  params:[] },
    { tab:'seosocial', section:'SEO',       id:'semantic_kw',   label:'Semantic Keyword Generator',desc:'Generate 20 LSI and semantically related keywords.',         needsText:false, params:[{key:'keyword',label:'Main keyword',type:'text',ph:'e.g. intermittent fasting',req:true}] },

    // ── SOCIAL ───────────────────────────────────────────────────────
    { tab:'seosocial', section:'YouTube',   id:'yt_vid_title',  label:'Video Title Generator',     desc:'Generate 5 clickable YouTube video titles.',                needsText:false, params:[{key:'topic',label:'Topic',type:'text',ph:'e.g. beginner home workout routine',req:false}] },
    { tab:'seosocial', section:'YouTube',   id:'yt_vid_desc',   label:'Video Description Generator',desc:'Write a full YouTube description with hooks and hashtags.',  needsText:true,  params:[] },
    { tab:'seosocial', section:'YouTube',   id:'yt_tags_gen',   label:'Tag Generator',             desc:'Generate 20 YouTube tags as a comma-separated list.',        needsText:false, params:[{key:'topic',label:'Topic',type:'text',ph:'e.g. vegan meal prep ideas',req:false}] },

    { tab:'seosocial', section:'Instagram', id:'ig_caption',    label:'Caption Generator',         desc:'Write an Instagram caption with hashtags.',                 needsText:true,  params:[{key:'tone',label:'Tone',type:'select',opts:['engaging','funny','inspirational','professional','casual'],req:false}] },
    { tab:'seosocial', section:'Instagram', id:'ig_bio',        label:'Bio Generator',             desc:'Write 3 Instagram bio options (≤150 chars each).',          needsText:false, params:[{key:'niche',label:'Niche / brand',type:'text',ph:'e.g. fitness coach for busy moms',req:true}] },
    { tab:'seosocial', section:'Instagram', id:'ig_hashtags',   label:'Hashtag Generator',         desc:'Generate 30 hashtags mixed by reach tier.',                 needsText:false, params:[{key:'topic',label:'Topic',type:'text',ph:'e.g. minimalist home decor',req:false}] },

    { tab:'seosocial', section:'LinkedIn',  id:'li_post',       label:'LinkedIn Post Generator',   desc:'Write a professional LinkedIn post with a hook and CTA.',   needsText:true,  params:[] },
    { tab:'seosocial', section:'LinkedIn',  id:'li_headline',   label:'LinkedIn Headline Generator',desc:'Write 5 LinkedIn headline options (≤120 chars).',            needsText:false, params:[{key:'role',label:'Role / expertise',type:'text',ph:'e.g. data analyst with 5 yrs in fintech',req:true}] },
    { tab:'seosocial', section:'LinkedIn',  id:'li_summary',    label:'LinkedIn Summary Generator',desc:'Write a 200–300 word LinkedIn About section.',               needsText:true,  params:[] },

    { tab:'seosocial', section:'X / Twitter', id:'tweet_gen',   label:'Tweet Generator',           desc:'Write a tweet (≤280 chars) with hashtags.',                 needsText:false, params:[{key:'topic',label:'Topic',type:'text',ph:'e.g. productivity tip for developers',req:false}] },
    { tab:'seosocial', section:'X / Twitter', id:'thread_gen',  label:'Thread Generator',          desc:'Write a 5-tweet thread with hook and CTA.',                 needsText:false, params:[{key:'topic',label:'Topic',type:'text',ph:'e.g. how to build a morning routine',req:false}] },
    { tab:'seosocial', section:'X / Twitter', id:'tweet_rewrite',label:'Tweet Rewriter',           desc:'Rewrite a tweet to be more engaging (≤280 chars).',         needsText:true,  params:[] },

    // ── GENERATORS ──────────────────────────────────────────────────
    { tab:'generators', section:'Random Generators', id:'rand_word',    label:'Random Word Generator',     desc:'Generate 10 interesting random words with definitions.',    needsText:false, params:[{key:'category',label:'Category (optional)',type:'text',ph:'e.g. nature, technology, emotions',req:false}] },
    { tab:'generators', section:'Random Generators', id:'rand_sentence',label:'Random Sentence Generator', desc:'Generate 5 random creative sentences.',                     needsText:false, params:[{key:'theme',label:'Theme (optional)',type:'text',ph:'e.g. adventure, mystery, sci-fi',req:false}] },
    { tab:'generators', section:'Random Generators', id:'rand_para',    label:'Random Paragraph Generator',desc:'Generate a random paragraph on any topic.',                 needsText:false, params:[{key:'topic',label:'Topic (optional)',type:'text',ph:'leave blank for random',req:false}] },
    { tab:'generators', section:'Random Generators', id:'rand_quote',   label:'Random Quote Generator',    desc:'Generate an original inspirational quote.',                needsText:false, params:[{key:'theme',label:'Theme',type:'text',ph:'e.g. resilience, creativity, leadership',req:false}] },
    { tab:'generators', section:'Name Generators',   id:'rand_name',    label:'Random Name Generator',     desc:'Generate 10 random full names from diverse backgrounds.',   needsText:false, params:[{key:'origin',label:'Cultural origin (optional)',type:'text',ph:'e.g. Japanese, Irish, Swahili',req:false}] },
    { tab:'generators', section:'Name Generators',   id:'username_gen', label:'Username Generator',        desc:'Generate 10 creative usernames for any niche.',            needsText:false, params:[{key:'niche',label:'Niche / style',type:'text',ph:'e.g. gaming, photography, fitness',req:false}] },
    { tab:'generators', section:'Name Generators',   id:'nickname_gen', label:'Nickname Generator',        desc:'Generate 10 nicknames for a given name.',                  needsText:false, params:[{key:'name',label:'Name',type:'text',ph:'e.g. Alexandra, Michael',req:true}] },
    { tab:'generators', section:'Name Generators',   id:'pen_name_gen', label:'Pen Name Generator',        desc:'Generate 10 pen name ideas for any writing genre.',        needsText:false, params:[{key:'genre',label:'Genre',type:'text',ph:'e.g. thriller, romance, sci-fi',req:false}] },
    { tab:'generators', section:'Name Generators',   id:'fake_company', label:'Fake Company Name Generator',desc:'Generate 10 realistic fake company names.',               needsText:false, params:[{key:'industry',label:'Industry',type:'text',ph:'e.g. fintech, sustainable fashion',req:false}] },
  ];

  /* ── Utility: simple markdown → HTML ────────────────────────────────── */
  function mdToHtml(text) {
    return text
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      // **bold**
      .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
      // *italic*
      .replace(/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/g,'<em>$1</em>')
      // ### h3
      .replace(/^### (.+)$/gm,'<h3 class="xt-ai-h3">$1</h3>')
      // ## h2
      .replace(/^## (.+)$/gm,'<h2 class="xt-ai-h2">$2</h2>'.replace('$2','$1'))
      // # h1
      .replace(/^# (.+)$/gm,'<h2 class="xt-ai-h2">$1</h2>')
      // numbered list
      .replace(/^\d+\.\s+(.+)$/gm,'<li>$1</li>')
      // bullet list
      .replace(/^[-*]\s+(.+)$/gm,'<li>$1</li>')
      // wrap consecutive <li> in <ol> or <ul> - simplified: just wrap all <li>
      .replace(/(<li>[\s\S]*?<\/li>)/g, '<ul class="xt-ai-list">$1</ul>')
      // collapse nested ul
      .replace(/<\/ul>\s*<ul class="xt-ai-list">/g,'')
      // line breaks
      .replace(/\n\n/g,'<br><br>').replace(/\n/g,'<br>');
  }

  /* ── Group tools by section ─────────────────────────────────────────── */
  function groupBySection(tools) {
    var groups = {};
    var order  = [];
    tools.forEach(function(t) {
      if (!groups[t.section]) { groups[t.section] = []; order.push(t.section); }
      groups[t.section].push(t);
    });
    return { groups: groups, order: order };
  }

  /* ── Build param HTML ───────────────────────────────────────────────── */
  function buildParams(tool) {
    if (!tool.params.length && !tool.needsText) return '';
    var html = '<div class="xt-ai-params">';
    if (tool.needsText) {
      html += '<label class="xt-ai-param-row xt-ai-wb-row">'
            + '<span class="xt-ai-param-label">Workbench text</span>'
            + '<span class="xt-ai-wb-preview" data-wb>Uses text from the Workbench</span>'
            + '</label>';
    }
    tool.params.forEach(function(p) {
      html += '<label class="xt-ai-param-row">'
            + '<span class="xt-ai-param-label">' + p.label + (p.req ? ' <em>*</em>' : '') + '</span>';
      if (p.type === 'select') {
        html += '<select class="xt-ai-input" data-param="' + p.key + '">';
        (p.opts || []).forEach(function(o) { html += '<option value="' + o + '">' + o + '</option>'; });
        html += '</select>';
      } else if (p.type === 'textarea') {
        html += '<textarea class="xt-ai-input xt-ai-textarea" data-param="' + p.key + '" placeholder="' + (p.ph || '') + '" rows="3"></textarea>';
      } else {
        html += '<input class="xt-ai-input" type="text" data-param="' + p.key + '" placeholder="' + (p.ph || '') + '">';
      }
      html += '</label>';
    });
    html += '</div>';
    return html;
  }

  /* ── Render an AI pane ──────────────────────────────────────────────── */
  function renderAIPane(tabId) {
    var container = document.getElementById('xt-ai-' + tabId);
    if (!container) return 0;
    var paneTools = AI_TOOLS.filter(function(t){ return t.tab === tabId; });
    var g = groupBySection(paneTools);
    var html = '';
    g.order.forEach(function(section) {
      html += '<div class="xt-ai-section">'
            + '<div class="xt-ai-section-label">' + section + '</div>'
            + '<div class="xt-ai-cards">';
      g.groups[section].forEach(function(tool) {
        html += '<div class="xt-ai-card" id="aic-' + tool.id + '">'
              + '<div class="xt-ai-card-head">'
              + '<div class="xt-ai-card-title">' + tool.label + '</div>'
              + '<div class="xt-ai-card-desc">' + tool.desc + '</div>'
              + '</div>'
              + buildParams(tool)
              + '<div class="xt-ai-card-foot">'
              + '<button class="xt-ai-run" onclick="XT.runAI(this,\'' + tool.id + '\')" type="button">'
              + '<span class="xt-ai-run-label">Generate</span>'
              + '<span class="xt-ai-run-spin" style="display:none">Generating…</span>'
              + '</button>'
              + '</div>'
              + '<div class="xt-ai-result" style="display:none">'
              + '<div class="xt-ai-result-body"></div>'
              + '<div class="xt-ai-result-actions">'
              + '<button class="xt-ai-action-btn" onclick="XT.copyAIResult(this)" type="button">Copy</button>'
              + '<button class="xt-ai-action-btn" onclick="XT.sendAIToWB(this)" type="button">→ Workbench</button>'
              + '</div>'
              + '</div>'
              + '<div class="xt-ai-error" style="display:none"></div>'
              + '</div>';
      });
      html += '</div></div>';
    });
    container.innerHTML = html;
    return paneTools.length;
  }

  /* ── Render all AI panes ─────────────────────────────────────────────── */
  function renderAll() {
    var counts = { writing:0, aitools:0, academic:0, seosocial:0, generators:0 };
    Object.keys(counts).forEach(function(tab) {
      counts[tab] = renderAIPane(tab);
    });
    // Update tab badges
    Object.keys(counts).forEach(function(tab) {
      var el = document.getElementById('cnt-' + tab);
      if (el) el.textContent = counts[tab];
    });
    // Refresh workbench preview text in all wb-rows
    refreshWBPreviews();
  }

  /* ── Sync workbench preview labels ──────────────────────────────────── */
  function refreshWBPreviews() {
    var input = document.getElementById('xt-input');
    var val   = input ? input.value.trim() : '';
    var preview = val ? (val.length > 60 ? val.slice(0,60) + '…' : val) : 'Uses text from the Workbench';
    document.querySelectorAll('[data-wb]').forEach(function(el) {
      el.textContent = preview || 'Uses text from the Workbench';
    });
  }

  /* ── Run an AI tool ─────────────────────────────────────────────────── */
  function runAI(btn, toolId) {
    var card     = btn.closest('.xt-ai-card');
    var resultEl = card.querySelector('.xt-ai-result');
    var errorEl  = card.querySelector('.xt-ai-error');
    var labelEl  = card.querySelector('.xt-ai-run-label');
    var spinEl   = card.querySelector('.xt-ai-run-spin');

    // Gather params
    var params = {};
    card.querySelectorAll('[data-param]').forEach(function(el) {
      params[el.dataset.param] = el.value;
    });

    // Workbench text
    var inputEl = document.getElementById('xt-input');
    var text    = inputEl ? inputEl.value : '';

    // Loading state
    btn.disabled = true;
    labelEl.style.display = 'none';
    spinEl.style.display  = 'inline';
    resultEl.style.display = 'none';
    errorEl.style.display  = 'none';

    fetch('/plugins/text-toolkit/api', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ tool: toolId, text: text, params: params })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      btn.disabled = false;
      labelEl.style.display = 'inline';
      spinEl.style.display  = 'none';

      if (data.error) {
        errorEl.textContent    = data.error;
        errorEl.style.display  = 'block';
        return;
      }
      var body = card.querySelector('.xt-ai-result-body');
      body.innerHTML          = mdToHtml(data.result || '');
      body.dataset.raw        = data.result || '';
      resultEl.style.display  = 'block';
      resultEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    })
    .catch(function(err) {
      btn.disabled = false;
      labelEl.style.display = 'inline';
      spinEl.style.display  = 'none';
      errorEl.textContent   = 'Network error. Please try again.';
      errorEl.style.display = 'block';
    });
  }

  /* ── Copy result ─────────────────────────────────────────────────────── */
  function copyAIResult(btn) {
    var body = btn.closest('.xt-ai-result').querySelector('.xt-ai-result-body');
    var text = body.dataset.raw || body.innerText || '';
    if (!text) return;
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(function() {
        var orig = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(function(){ btn.textContent = orig; }, 1500);
      });
    } else {
      var ta = document.createElement('textarea');
      ta.value = text; document.body.appendChild(ta);
      ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
      var orig = btn.textContent;
      btn.textContent = 'Copied!';
      setTimeout(function(){ btn.textContent = orig; }, 1500);
    }
  }

  /* ── Send result → Workbench ─────────────────────────────────────────── */
  function sendAIToWB(btn) {
    var body  = btn.closest('.xt-ai-result').querySelector('.xt-ai-result-body');
    var text  = body.dataset.raw || body.innerText || '';
    var input = document.getElementById('xt-input');
    if (!input || !text) return;
    input.value = text;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    refreshWBPreviews();
    var orig = btn.textContent;
    btn.textContent = 'Sent ✓';
    setTimeout(function(){ btn.textContent = orig; }, 1500);
  }

  /* ── Extend global search index with AI tools ───────────────────────── */
  function extendSearch() {
    // The existing XT search index uses searchIndex array built in text-toolkit.js.
    // Extend it if the array is accessible, otherwise patch the search input handler.
    var TAB_MAP = {
      writing:'writing', aitools:'aitools',
      academic:'academic', seosocial:'seosocial', generators:'generators'
    };
    if (window._xtSearchIndex && Array.isArray(window._xtSearchIndex)) {
      AI_TOOLS.forEach(function(t) {
        window._xtSearchIndex.push({
          label: t.label, desc: t.desc,
          tab: TAB_MAP[t.tab], id: t.id, type: 'ai'
        });
      });
    }
  }

  /* ── Keep WB previews in sync as user types ─────────────────────────── */
  var wbInput = document.getElementById('xt-input');
  if (wbInput) {
    wbInput.addEventListener('input', refreshWBPreviews);
  }

  /* ── Tab switches: refresh previews when an AI tab is shown ─────────── */
  document.querySelectorAll('.xt-tab').forEach(function(btn) {
    btn.addEventListener('click', function() {
      setTimeout(refreshWBPreviews, 50);
    });
  });

  /* ── Init ────────────────────────────────────────────────────────────── */
  renderAll();
  extendSearch();

  /* ── Expose for inline onclick handlers ─────────────────────────────── */
  if (window.XT) {
    window.XT.runAI        = runAI;
    window.XT.copyAIResult = copyAIResult;
    window.XT.sendAIToWB   = sendAIToWB;
  }

})();
