<?php

namespace App\Http\Requests;

use App\Models\Movie;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Query parameters of GET /api/movies.
 * Invalid values return 422 with an error for each field.
 */
class MovieIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],

            'genre_ids' => ['nullable', 'array'],
            'genre_ids.*' => ['integer', 'exists:genres,id'],

            'age_ratings' => ['nullable', 'array'],
            'age_ratings.*' => ['string', Rule::in(Movie::AGE_RATINGS)],

            'date' => ['nullable', 'date_format:Y-m-d'],

            'sort' => ['nullable', Rule::in(['title', 'release_date'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],

            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * In a URL "+" means a space, so ?age_ratings[]=12+ arrives as "12 ".
     * Accept "12", "12 " and "12+" as the same rating.
     */
    protected function prepareForValidation(): void
    {
        $ratings = $this->input('age_ratings');

        if (! is_array($ratings)) {
            return;
        }

        $this->merge([
            'age_ratings' => array_map(function ($rating) {
                $rating = trim((string) $rating);

                return ctype_digit($rating) ? $rating.'+' : $rating;
            }, $ratings),
        ]);
    }
}
