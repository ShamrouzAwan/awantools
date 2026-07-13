Text Toolkit

URL: /text-toolkit

STATUS (updated 2026-07-13)
================================================================================
BUILT — plugins/text-toolkit/ (live at /plugins/text-toolkit/, active in DB)
--------------------------------------------------------------------------------
109 purely algorithmic, 100% client-side tools, shipped as a single "Workbench"
UI: one shared textarea on the left that every tool reads/writes live, plus a
6-tab panel on the right. Covers sections 1-7, 10 and 18 below (all tagged
[BUILT] inline). Tab breakdown:
  - Analysis (27 tools)      = section 1 (Counters + Reading Metrics) + section 10 (Readability)
  - Format & Case (29 tools) = section 2 (Text Formatting) + section 3 (Case Conversion)
  - Cleanup & Utilities (22) = section 4 (Text Cleanup) + section 18 (Text Utilities)
  - Extraction (11 tools)    = section 5
  - Comparison (8 tools)     = section 6
  - Encoding (12 tools)      = section 7
Files: plugins/text-toolkit/plugin.json, index.php, assets/text-toolkit.css,
assets/text-toolkit.js.

NOT YET BUILT
--------------------------------------------------------------------------------
- Sections 8, 9, 11, 12, 13, 14, 15, 16 [PENDING - AI/LLM] — deferred by explicit
  decision: these ~148 tools need a server-side LLM call (grammar checking,
  rewriting/paraphrasing, AI detection/humanizing, academic writing, translation,
  SEO/social/business copy generation). Not started — needs an LLM provider
  decision (Replit AI integration or external API key) before any of these can
  be built.
- Section 17, "Generator Toolkit" (9 tools) [PENDING - not yet built] — these are
  algorithmic (random word/sentence/paragraph/quote/name/username/nickname/pen
  name/fake company generators), not AI-dependent, but were not part of the
  109-tool build. Could be added to the existing plugin without an LLM.
================================================================================

1. Text Analysis Toolkit [BUILT]
Counters
Word Counter
Character Counter
Character Counter (No Spaces)
Sentence Counter
Paragraph Counter
Line Counter
Letter Counter
Digit Counter
Symbol Counter
Punctuation Counter
Reading Metrics
Reading Time Calculator
Speaking Time Calculator
Average Word Length
Average Sentence Length
Lexical Density Calculator
Vocabulary Richness Analyzer
Keyword Density Checker
Keyword Frequency Analyzer
Most Used Words Finder
N-Gram Analyzer
2. Text Formatting Toolkit [BUILT]
Uppercase Converter
Lowercase Converter
Sentence Case Converter
Title Case Converter
Toggle Case Converter
Invert Case Converter
Alternating Case Converter
Random Case Generator
Remove Extra Spaces
Trim Text
Normalize Whitespace
Remove Blank Lines
Remove Duplicate Lines
Sort Lines A-Z
Sort Lines Z-A
Reverse Text
Reverse Words
Reverse Sentences
Reverse Lines
Text Wrapper
3. Case Conversion Toolkit [BUILT]
Camel Case Converter
Pascal Case Converter
Snake Case Converter
Kebab Case Converter
Dot Case Converter
Constant Case Converter
Path Case Converter
Header Case Converter
Train Case Converter
4. Text Cleanup Toolkit [BUILT]
Remove Numbers
Remove Letters
Remove Special Characters
Remove Punctuation
Remove HTML Tags
Remove Emojis
Remove Duplicate Words
Remove Duplicate Sentences
Remove Duplicate Paragraphs
Find and Replace
Batch Find and Replace
Text Sanitizer
5. Extraction Toolkit [BUILT]
Extract Emails
Extract URLs
Extract Domains
Extract Phone Numbers
Extract Hashtags
Extract Mentions
Extract Numbers
Extract Dates
Extract Addresses
Extract IP Addresses
Extract Social Media Handles
6. Text Comparison Toolkit [BUILT]
Text Compare
Side-by-Side Compare
Similarity Checker
Difference Checker
Duplicate Detector
Paragraph Compare
Sentence Compare
Word Compare
7. Text Encoding Toolkit [BUILT]
Base64 Encode
Base64 Decode
ROT13 Encode
ROT13 Decode
Morse Code Encode
Morse Code Decode
ASCII Converter
Unicode Converter
Binary Converter
Hex Converter
URL Encode
URL Decode
8. Writing Assistant Toolkit [PENDING - AI/LLM]
Headlines
Headline Generator
Blog Title Generator
Article Title Generator
YouTube Title Generator
Email Subject Generator
Ad Headline Generator
Content
Introduction Generator
Conclusion Generator
Outline Generator
Paragraph Generator
Hook Generator
CTA Generator
Summary Generator
Marketing
Product Description Generator
Sales Copy Generator
Landing Page Copy Generator
Ad Copy Generator
Facebook Ad Generator
Google Ad Generator
9. Grammar Toolkit [PENDING - AI/LLM]
Grammar Checker
Spelling Checker
Punctuation Checker
Capitalization Checker
Sentence Corrector
Passive Voice Checker
Active Voice Converter
Writing Clarity Checker
Readability Checker
10. Readability Toolkit [BUILT — merged into the Analysis tab]
Flesch Reading Ease
Gunning Fog Index
SMOG Index
Coleman-Liau Index
Automated Readability Index
Reading Grade Calculator
Reading Difficulty Checker
11. AI Toolkit [PENDING - AI/LLM]

This is where huge traffic lives.

AI Detection
AI Content Detector
GPT Content Detector
ChatGPT Detector
AI Probability Checker
AI Score Calculator
Humanization
AI Humanizer
AI Text Rewriter
Human Tone Converter
Natural Language Converter
Undetectable AI Rewriter
Rewriting
Paraphrasing Tool
Sentence Rewriter
Paragraph Rewriter
Article Rewriter
Content Improver
Content Expander
Content Shortener
Optimization
Clarity Improver
Fluency Enhancer
Formality Adjuster
Tone Converter
Simplicity Converter
12. Academic Toolkit [PENDING - AI/LLM]
Research
Plagiarism Checker
Duplicate Content Checker
Source Similarity Checker
Citation Generator
Reference Generator
Citation Styles
APA Generator
MLA Generator
Chicago Generator
Harvard Generator
IEEE Generator
Student Tools
Essay Generator
Thesis Statement Generator
Research Question Generator
Abstract Generator
Literature Review Generator
13. Language Toolkit [PENDING - AI/LLM]
Translation
Language Translator
Text Localizer
Language Detector
Linguistics
Synonym Finder
Antonym Finder
Rhyming Words Finder
Dictionary Lookup
Phrase Meaning Finder
Idiom Finder
Vocabulary
Vocabulary Enhancer
Simplify Text
Advanced Vocabulary Generator
Common Words Replacer
14. SEO Content Toolkit [PENDING - AI/LLM]
Keyword Inserter
Keyword Density Optimizer
SEO Title Generator
SEO Meta Description Generator
Featured Snippet Optimizer
Content Gap Analyzer
Semantic Keyword Generator
15. Social Media Writing Toolkit [PENDING - AI/LLM]
YouTube
Video Title Generator
Video Description Generator
Tag Generator
Instagram
Caption Generator
Bio Generator
Hashtag Generator
LinkedIn
LinkedIn Post Generator
LinkedIn Headline Generator
LinkedIn Summary Generator
X/Twitter
Tweet Generator
Thread Generator
Tweet Rewriter
16. Business Writing Toolkit [PENDING - AI/LLM]
Business Name Generator
Brand Name Generator
Slogan Generator
Tagline Generator
Mission Statement Generator
Vision Statement Generator
Elevator Pitch Generator
17. Generator Toolkit [PENDING - not yet built, no AI needed]
Random Word Generator
Random Sentence Generator
Random Paragraph Generator
Random Quote Generator
Random Name Generator
Username Generator
Nickname Generator
Pen Name Generator
Fake Company Name Generator
18. Text Utilities Toolkit [BUILT — merged into the Cleanup & Utilities tab]
Text Splitter
Text Merger
Text Chunker
Bullet List Generator
Numbered List Generator
CSV to List
List to CSV
Alphabetizer
Text Shuffle Tool
Text Deduplicator
