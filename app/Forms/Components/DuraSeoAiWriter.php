<?php

declare(strict_types=1);

namespace App\Forms\Components;

use App\SEO\Services\SeoAiWriterService;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Throwable;

final class DuraSeoAiWriter extends Section
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->heading('Dura SEO AI Writer')
            ->description(
                'AI se SEO title, meta description, focus keyword, slug, article content aur FAQ generate karein.',
            )
            ->icon('heroicon-o-sparkles')
            ->collapsible()
            ->collapsed()
            ->compact()
            ->schema([
                Grid::make([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 3,
                ])
                    ->schema([
                        TextInput::make('seo_ai_topic')
                            ->label('Topic')
                            ->placeholder(
                                'Example: Agra to Delhi Taxi Service',
                            )
                            ->helperText(
                                'Page ya product ka main subject enter karein.',
                            )
                            ->maxLength(255)
                            ->dehydrated(false),

                        TextInput::make('seo_ai_keyword')
                            ->label('Focus Keyword')
                            ->placeholder(
                                'Example: Agra to Delhi taxi',
                            )
                            ->helperText(
                                'Blank rehne par topic ko focus keyword maana jayega.',
                            )
                            ->maxLength(150)
                            ->dehydrated(false),

                        TextInput::make('seo_ai_city')
                            ->label('Target City')
                            ->placeholder('Example: Agra')
                            ->maxLength(120)
                            ->dehydrated(false),

                        Select::make('seo_ai_service_type')
                            ->label('Service Type')
                            ->options([
                                'one_way_taxi' => 'One Way Taxi',
                                'round_trip_taxi' => 'Round Trip Taxi',
                                'airport_taxi' => 'Airport Taxi',
                                'local_taxi' => 'Local Taxi',
                                'tour_package' => 'Tour Package',
                                'self_drive_car' => 'Self Drive Car',
                                'fleet_service' => 'Fleet Service',
                                'product' => 'Product',
                                'landing_page' => 'Landing Page',
                                'blog_article' => 'Blog Article',
                                'other' => 'Other',
                            ])
                            ->default('product')
                            ->native(false)
                            ->searchable()
                            ->dehydrated(false),

                        Select::make('seo_ai_language')
                            ->label('Language')
                            ->options([
                                'English' => 'English',
                                'Hindi' => 'Hindi',
                                'Hinglish' => 'Hinglish',
                            ])
                            ->default(
                                fn (): string => (string) config(
                                    'seo-ai.writer.language',
                                    'English',
                                ),
                            )
                            ->native(false)
                            ->dehydrated(false),

                        Select::make('seo_ai_tone')
                            ->label('Writing Tone')
                            ->options([
                                'Professional' => 'Professional',
                                'Conversational' => 'Conversational',
                                'Friendly' => 'Friendly',
                                'Informative' => 'Informative',
                                'Persuasive' => 'Persuasive',
                                'Luxury' => 'Luxury',
                                'Local SEO' => 'Local SEO',
                                'Travel Expert' => 'Travel Expert',
                            ])
                            ->default(
                                fn (): string => (string) config(
                                    'seo-ai.writer.tone',
                                    'Professional',
                                ),
                            )
                            ->native(false)
                            ->dehydrated(false),

                        Select::make('seo_ai_word_count')
                            ->label('Article Length')
                            ->options([
                                300 => 'Short — 300 words',
                                500 => 'Brief — 500 words',
                                800 => 'Standard — 800 words',
                                1000 => 'Detailed — 1,000 words',
                                1500 => 'Long — 1,500 words',
                                2000 => 'Authority — 2,000 words',
                                3000 => 'Pillar Page — 3,000 words',
                            ])
                            ->default(
                                fn (): int => (int) config(
                                    'seo-ai.writer.word_count',
                                    1000,
                                ),
                            )
                            ->native(false)
                            ->dehydrated(false),

                        Select::make('seo_ai_faq_count')
                            ->label('FAQ Count')
                            ->options([
                                0 => 'No FAQ',
                                3 => '3 FAQs',
                                5 => '5 FAQs',
                                8 => '8 FAQs',
                                10 => '10 FAQs',
                            ])
                            ->default(
                                fn (): int => (int) config(
                                    'seo-ai.writer.max_faqs',
                                    5,
                                ),
                            )
                            ->native(false)
                            ->dehydrated(false),

                        TextInput::make('seo_ai_provider')
                            ->label('AI Provider')
                            ->default(
                                fn (): string => (string) config(
                                    'seo-ai.default',
                                    'openai',
                                ),
                            )
                            ->readOnly()
                            ->dehydrated(false),
                    ]),

                Textarea::make('seo_ai_instructions')
                    ->label('Additional Instructions')
                    ->placeholder(
                        'Example: Airport pickup, verified drivers aur 24×7 booking support ko naturally mention karein. Koi fake price add na karein.',
                    )
                    ->helperText(
                        'Business facts, important points aur content requirements yahan add karein.',
                    )
                    ->rows(3)
                    ->maxLength(2000)
                    ->dehydrated(false)
                    ->columnSpanFull(),

                Actions::make([
                    Action::make('generateSeoAiContent')
                        ->label('Generate Complete SEO Content')
                        ->icon('heroicon-o-sparkles')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Generate SEO content with AI?')
                        ->modalDescription(
                            'Generated data existing SEO title, description, slug, focus keyword aur page content ko replace kar sakta hai.',
                        )
                        ->modalSubmitActionLabel('Generate Content')
                        ->action(
                            function (Get $get, Set $set): void {
                                $this->generateCompleteContent(
                                    get: $get,
                                    set: $set,
                                );
                            },
                        ),

                    Action::make('prepareSeoAiFields')
                        ->label('Use Current Page Data')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->action(
                            function (Get $get, Set $set): void {
                                $this->prepareFieldsFromCurrentPage(
                                    get: $get,
                                    set: $set,
                                );
                            },
                        ),

                    Action::make('clearSeoAiFields')
                        ->label('Clear AI Inputs')
                        ->icon('heroicon-o-x-mark')
                        ->color('gray')
                        ->action(
                            function (Set $set): void {
                                $this->clearAiInputFields($set);

                                Notification::make()
                                    ->title('AI input fields cleared')
                                    ->success()
                                    ->send();
                            },
                        ),
                ])
                    ->fullWidth(),

                Placeholder::make('seo_ai_generated_summary')
                    ->label('Latest AI Output')
                    ->content(
                        fn (Get $get): HtmlString => $this->renderGeneratedSummary(
                            $get,
                        ),
                    )
                    ->columnSpanFull(),

                Textarea::make('seo_ai_generated_faqs')
                    ->label('Generated FAQs')
                    ->rows(10)
                    ->readOnly()
                    ->dehydrated(false)
                    ->visible(
                        fn (Get $get): bool => trim(
                            (string) $get('seo_ai_generated_faqs'),
                        ) !== '',
                    )
                    ->columnSpanFull(),
            ]);
    }

    private function generateCompleteContent(
        Get $get,
        Set $set,
    ): void {
        $topic = trim(
            (string) (
                $get('seo_ai_topic')
                ?: $get('name')
                ?: $get('meta_title')
                ?: ''
            ),
        );

        if ($topic === '') {
            Notification::make()
                ->title('Topic required')
                ->body(
                    'AI content generate karne ke liye Topic ya page Name enter karein.',
                )
                ->danger()
                ->send();

            return;
        }

        $focusKeyword = trim(
            (string) (
                $get('seo_ai_keyword')
                ?: $get('focus_keyword')
                ?: $topic
            ),
        );

        try {
            $result = app(SeoAiWriterService::class)->generate([
                'topic' => $topic,

                'focus_keyword' => $focusKeyword,

                'city' => trim(
                    (string) $get('seo_ai_city'),
                ),

                'service_type' => $this->serviceTypeLabel(
                    (string) $get('seo_ai_service_type'),
                ),

                'language' => trim(
                    (string) (
                        $get('seo_ai_language')
                        ?: config(
                            'seo-ai.writer.language',
                            'English',
                        )
                    ),
                ),

                'tone' => trim(
                    (string) (
                        $get('seo_ai_tone')
                        ?: config(
                            'seo-ai.writer.tone',
                            'Professional',
                        )
                    ),
                ),

                'word_count' => (int) (
                    $get('seo_ai_word_count')
                    ?: config(
                        'seo-ai.writer.word_count',
                        1000,
                    )
                ),

                'max_faqs' => (int) (
                    $get('seo_ai_faq_count')
                    ?? config(
                        'seo-ai.writer.max_faqs',
                        5,
                    )
                ),

                'existing_content' => trim(
                    (string) (
                        $get('description')
                        ?: $get('content')
                        ?: ''
                    ),
                ),

                'additional_instructions' => trim(
                    (string) $get('seo_ai_instructions'),
                ),

                'provider' => trim(
                    (string) (
                        $get('seo_ai_provider')
                        ?: config('seo-ai.default', 'openai')
                    ),
                ),
            ]);

            $this->applyGeneratedContent(
                result: $result,
                set: $set,
            );

            Notification::make()
                ->title('SEO content generated successfully')
                ->body(
                    'SEO title, meta description, slug, focus keyword aur content form me apply kar diya gaya hai.',
                )
                ->success()
                ->duration(7000)
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('AI content generate nahi hua')
                ->body(
                    $this->safeErrorMessage($exception),
                )
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /**
     * @param array<string, mixed> $result
     */
    private function applyGeneratedContent(
        array $result,
        Set $set,
    ): void {
        $seoTitle = trim(
            (string) (
                $result['meta_title']
                ?? $result['seo_title']
                ?? ''
            ),
        );

        $metaDescription = trim(
            (string) ($result['meta_description'] ?? ''),
        );

        $slug = trim(
            (string) ($result['slug'] ?? ''),
        );

        $focusKeyword = trim(
            (string) ($result['focus_keyword'] ?? ''),
        );

        $content = trim(
            (string) (
                $result['description']
                ?? $result['content']
                ?? ''
            ),
        );

        if ($seoTitle !== '') {
            $set('meta_title', $seoTitle);
        }

        if ($metaDescription !== '') {
            $set('meta_description', $metaDescription);
        }

        if ($slug !== '') {
            $set('slug', Str::slug($slug));
        }

        if ($focusKeyword !== '') {
            $set('focus_keyword', $focusKeyword);
            $set('seo_ai_keyword', $focusKeyword);
        }

        if ($content !== '') {
            $set('description', $content);
            $set('content', $content);
        }

        $set(
            'seo_ai_generated_title',
            $seoTitle,
        );

        $set(
            'seo_ai_generated_meta_description',
            $metaDescription,
        );

        $set(
            'seo_ai_generated_content_length',
            Str::wordCount(
                strip_tags($content),
            ),
        );

        $set(
            'seo_ai_generated_faqs',
            $this->formatFaqs(
                $result['faqs'] ?? [],
            ),
        );
    }

    private function prepareFieldsFromCurrentPage(
        Get $get,
        Set $set,
    ): void {
        $name = trim(
            (string) $get('name'),
        );

        $metaTitle = trim(
            (string) $get('meta_title'),
        );

        $focusKeyword = trim(
            (string) $get('focus_keyword'),
        );

        $set(
            'seo_ai_topic',
            $name !== ''
                ? $name
                : $metaTitle,
        );

        $set(
            'seo_ai_keyword',
            $focusKeyword !== ''
                ? $focusKeyword
                : ($name !== '' ? $name : $metaTitle),
        );

        Notification::make()
            ->title('Current page data loaded')
            ->body(
                'Topic aur focus keyword current form data se prepare kar diye gaye hain.',
            )
            ->success()
            ->send();
    }

    private function clearAiInputFields(Set $set): void
    {
        $set('seo_ai_topic', null);
        $set('seo_ai_keyword', null);
        $set('seo_ai_city', null);
        $set('seo_ai_instructions', null);
        $set('seo_ai_generated_title', null);
        $set('seo_ai_generated_meta_description', null);
        $set('seo_ai_generated_content_length', null);
        $set('seo_ai_generated_faqs', null);
    }

    private function renderGeneratedSummary(Get $get): HtmlString
    {
        $title = trim(
            (string) $get('seo_ai_generated_title'),
        );

        $description = trim(
            (string) $get(
                'seo_ai_generated_meta_description',
            ),
        );

        $wordCount = (int) (
            $get('seo_ai_generated_content_length')
            ?: 0
        );

        if (
            $title === ''
            && $description === ''
            && $wordCount === 0
        ) {
            return new HtmlString(
                <<<'HTML'
                <div
                    style="
                        padding: 14px;
                        border: 1px dashed rgba(148, 163, 184, 0.45);
                        border-radius: 10px;
                        color: #64748b;
                        font-size: 13px;
                    "
                >
                    Abhi tak koi AI content generate nahi hua hai.
                </div>
                HTML,
            );
        }

        $safeTitle = e(
            $title !== ''
                ? $title
                : 'Not generated',
        );

        $safeDescription = e(
            $description !== ''
                ? $description
                : 'Not generated',
        );

        $formattedWordCount = number_format($wordCount);

        return new HtmlString(
            <<<HTML
            <div
                style="
                    display: grid;
                    gap: 10px;
                    padding: 15px;
                    border: 1px solid rgba(148, 163, 184, 0.28);
                    border-radius: 12px;
                    background: rgba(148, 163, 184, 0.07);
                "
            >
                <div>
                    <div
                        style="
                            color: #64748b;
                            font-size: 11px;
                            font-weight: 600;
                            text-transform: uppercase;
                        "
                    >
                        Generated SEO Title
                    </div>

                    <div
                        style="
                            margin-top: 3px;
                            font-size: 14px;
                            font-weight: 700;
                        "
                    >
                        {$safeTitle}
                    </div>
                </div>

                <div>
                    <div
                        style="
                            color: #64748b;
                            font-size: 11px;
                            font-weight: 600;
                            text-transform: uppercase;
                        "
                    >
                        Generated Meta Description
                    </div>

                    <div
                        style="
                            margin-top: 3px;
                            color: #475569;
                            font-size: 13px;
                            line-height: 1.5;
                        "
                    >
                        {$safeDescription}
                    </div>
                </div>

                <div
                    style="
                        font-size: 12px;
                        color: #64748b;
                    "
                >
                    Generated content words:
                    <strong>{$formattedWordCount}</strong>
                </div>
            </div>
            HTML,
        );
    }

    /**
     * @param mixed $faqs
     */
    private function formatFaqs(mixed $faqs): string
    {
        if (! is_array($faqs)) {
            return '';
        }

        $formatted = [];

        foreach ($faqs as $index => $faq) {
            if (! is_array($faq)) {
                continue;
            }

            $question = trim(
                (string) ($faq['question'] ?? ''),
            );

            $answer = trim(
                (string) ($faq['answer'] ?? ''),
            );

            if ($question === '' || $answer === '') {
                continue;
            }

            $number = count($formatted) + 1;

            $formatted[] = implode("\n", [
                "Q{$number}. {$question}",
                "A. {$answer}",
            ]);
        }

        return implode(
            "\n\n",
            $formatted,
        );
    }

    private function serviceTypeLabel(string $value): string
    {
        return match ($value) {
            'one_way_taxi' => 'One Way Taxi',
            'round_trip_taxi' => 'Round Trip Taxi',
            'airport_taxi' => 'Airport Taxi',
            'local_taxi' => 'Local Taxi',
            'tour_package' => 'Tour Package',
            'self_drive_car' => 'Self Drive Car',
            'fleet_service' => 'Fleet Service',
            'landing_page' => 'Landing Page',
            'blog_article' => 'Blog Article',
            'other' => 'Other',
            default => 'Product',
        };
    }

    private function safeErrorMessage(
        Throwable $exception,
    ): string {
        $message = trim($exception->getMessage());

        if ($message === '') {
            return 'Unknown AI service error. Laravel log check karein.';
        }

        return Str::limit(
            $message,
            350,
            '…',
        );
    }
}