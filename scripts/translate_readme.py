#!/usr/bin/env python3
"""
README Translation Script using Claude API

This script translates README.md into multiple languages using Claude API
with parallel processing for efficiency.

Usage:
    python scripts/translate_readme.py

Requirements:
    pip install -r scripts/requirements.txt

Environment Variables:
    ANTHROPIC_API_KEY: Your Claude API key (can be set in .env file)
"""

import asyncio
import os
import sys
from pathlib import Path
from typing import Dict, List

try:
    import anthropic
    import aiofiles
    from dotenv import load_dotenv
except ImportError as e:
    print(f"Error: Required packages not found. Install with: pip install -r scripts/requirements.txt")
    print(f"Missing: {e}")
    sys.exit(1)

# Language configurations
LANGUAGES = {
    "es": {
        "name": "Español",
        "filename": "README.es.md",
        "locale": "Spanish (Spain)"
    },
    "pt-BR": {
        "name": "Português do Brasil",
        "filename": "README.pt-BR.md",
        "locale": "Brazilian Portuguese"
    },
    "ko": {
        "name": "한국어",
        "filename": "README.ko.md",
        "locale": "Korean"
    },
    "ru": {
        "name": "Русский",
        "filename": "README.ru.md",
        "locale": "Russian"
    },
    "zh-CN": {
        "name": "简体中文",
        "filename": "README.zh-CN.md",
        "locale": "Simplified Chinese (China)"
    },
    "zh-TW": {
        "name": "繁體中文",
        "filename": "README.zh-TW.md",
        "locale": "Traditional Chinese (Taiwan)"
    },
    "pl": {
        "name": "Polski",
        "filename": "README.pl.md",
        "locale": "Polish"
    }
}

SYSTEM_PROMPT = """You are a native {target_language} professional technical documentation writer specializing in software development documentation. You are an expert at translating Laravel package documentation while maintaining technical accuracy and natural language flow.

CRITICAL REQUIREMENTS:
1. **Preserve ALL technical elements exactly**:
   - Code blocks, commands, file paths, URLs
   - Package names, class names, method names
   - Configuration keys, environment variables
   - All markdown formatting and structure

2. **Technical accuracy**:
   - Keep Laravel/PHP terminology consistent
   - Maintain proper technical context
   - Preserve all code examples unchanged
   - Use the English words if the term is even popular for software engineers in {target_language}

3. **Quality translation**:
   - Use natural, fluent and very local {target_language}
   - Adapt for {target_language} technical documentation style
   - Maintain opensource geek tone throughout
   - Use local idioms and expressions

4. **DO NOT translate**:
   - Code snippets and commands
   - URLs and links
   - Package/class/method names
   - Configuration file contents
   - Environment variable names
   - File paths and directory names

5. **Structure preservation**:
   - Keep exact same markdown hierarchy
   - Preserve all headers, lists, tables
   - Maintain all badges and links
   - Keep language selector links unchanged"""

USER_PROMPT = """Please translate this Laravel package README from English to {target_language}.

<content>
{content}
</content>

Return ONLY the translated content without any additional commentary or explanation."""

class ReadmeTranslator:
    def __init__(self):
        self.client = anthropic.Anthropic()
        self.project_root = Path(__file__).parent.parent
        self.readme_path = self.project_root / "README.md"

    async def read_readme(self) -> str:
        """Read the source README.md file."""
        async with aiofiles.open(self.readme_path, 'r', encoding='utf-8') as f:
            return await f.read()

    async def translate_to_language(self, content: str, lang_code: str, lang_config: Dict) -> str:
        """Translate content to a specific language using Claude."""
        print(f"🌐 Translating to {lang_config['name']}...")

        try:
            # Run the synchronous API call in a thread pool to make it non-blocking
            loop = asyncio.get_event_loop()
            message = await loop.run_in_executor(
                None,
                lambda: self.client.messages.create(
                    model="claude-sonnet-4-20250514",
                    max_tokens=8000,
                    temperature=0.5,
                    system=SYSTEM_PROMPT.format(target_language=lang_config['locale']),
                    messages=[{
                        "role": "user",
                        "content": USER_PROMPT.format(
                            target_language=lang_config['locale'],
                            content=content
                        )
                    }]
                )
            )

            translated_content = message.content[0].text
            print(f"✅ {lang_config['name']} translation completed")
            return translated_content

        except Exception as e:
            print(f"❌ Error translating to {lang_config['name']}: {e}")
            raise

    async def save_translation(self, content: str, filename: str) -> None:
        """Save translated content to file."""
        output_path = self.project_root / filename
        async with aiofiles.open(output_path, 'w', encoding='utf-8') as f:
            await f.write(content)
        print(f"💾 Saved {filename}")

    async def translate_language(self, content: str, lang_code: str, lang_config: Dict) -> None:
        """Translate and save a single language."""
        try:
            translated_content = await self.translate_to_language(content, lang_code, lang_config)
            await self.save_translation(translated_content, lang_config['filename'])
        except Exception as e:
            print(f"❌ Failed to process {lang_config['name']}: {e}")

    async def translate_all(self, languages: List[str] = None) -> None:
        """Translate README to all specified languages in parallel."""
        # Read source content
        print("📖 Reading README.md...")
        content = await self.read_readme()

        # Filter languages if specified
        target_languages = languages or list(LANGUAGES.keys())
        tasks = []

        print(f"🚀 Starting parallel translation for {len(target_languages)} languages...")

        # Create translation tasks
        for lang_code in target_languages:
            if lang_code in LANGUAGES:
                task = self.translate_language(content, lang_code, LANGUAGES[lang_code])
                tasks.append(task)
            else:
                print(f"⚠️  Unknown language code: {lang_code}")

        # Execute all translations in parallel
        await asyncio.gather(*tasks, return_exceptions=True)
        print("🎉 All translations completed!")

def check_api_key():
    """Check if Anthropic API key is available."""
    # Load environment variables from .env file
    load_dotenv()

    api_key = os.getenv('ANTHROPIC_API_KEY')
    if not api_key:
        print("❌ Error: ANTHROPIC_API_KEY environment variable not set")
        print("Please set your Claude API key in .env file or environment:")
        print("ANTHROPIC_API_KEY=your-api-key-here")
        sys.exit(1)
    return api_key

async def main():
    """Main entry point."""
    # Check for API key
    check_api_key()

    # Parse command line arguments
    target_languages = sys.argv[1:] if len(sys.argv) > 1 else None

    if target_languages:
        print(f"🎯 Translating to specific languages: {', '.join(target_languages)}")
    else:
        print("🌍 Translating to all supported languages")

    # Create translator and run
    translator = ReadmeTranslator()
    await translator.translate_all(target_languages)

if __name__ == "__main__":
    try:
        asyncio.run(main())
    except KeyboardInterrupt:
        print("\n⚠️ Translation interrupted by user")
    except Exception as e:
        print(f"❌ Translation failed: {e}")
        sys.exit(1)
