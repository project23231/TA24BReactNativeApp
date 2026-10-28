<?php

namespace Tests\Feature;

use App\Models\Cinema;
use App\Models\Hall;
use App\Models\Movie;
use App\Models\Screening;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SessionScheduleTest extends TestCase
{
    use DatabaseTransactions;

    private int $hallNumber = 0;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function movie(string $title = 'A cinema story'): Movie
    {
        return Movie::create([
            'title' => $title, 'description' => 'A film for schedule tests.',
            'duration_minutes' => 60, 'age_rating' => '12+',
        ]);
    }

    private function cinema(string $name = 'Apollo'): Cinema
    {
        return Cinema::create(['name' => $name, 'city' => 'Tallinn', 'address' => 'Test street 1']);
    }

    private function screening(Movie $movie, Cinema $cinema, string $start, string $status = 'scheduled'): Screening
    {
        $hall = Hall::create(['cinema_id' => $cinema->id, 'name' => 'Hall '.++$this->hallNumber]);
        $startsAt = Carbon::parse($start)->utc();

        return Screening::create([
            'movie_id' => $movie->id, 'hall_id' => $hall->id,
            'starts_at' => $startsAt, 'ends_at' => $startsAt->copy()->addHour(),
            'price' => '8.50', 'language' => 'en', 'subtitles' => 'et',
            'format' => '2D', 'status' => $status,
        ]);
    }

    public function test_tallinn_dst_day_includes_both_boundaries_correctly(): void
    {
        Carbon::setTestNow(Carbon::parse('2027-03-01T00:00:00Z'));
        $movie = $this->movie();
        $cinema = $this->cinema();
        $this->screening($movie, $cinema, '2027-03-27T21:59:00Z');
        $first = $this->screening($movie, $cinema, '2027-03-27T22:00:00Z');
        $last = $this->screening($movie, $cinema, '2027-03-28T20:59:00Z');
        $this->screening($movie, $cinema, '2027-03-28T21:00:00Z');
        $this->screening($movie, $cinema, '2027-03-28T10:00:00Z', 'cancelled');

        $this->getJson('/api/screenings?date=2027-03-28')->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.1.id', $last->id)
            ->assertJsonPath('data.0.movie.title', $movie->title)
            ->assertJsonPath('data.0.hall.cinema.name', 'Apollo')
            ->assertJsonPath('data.0.price', '8.50');
    }

    public function test_autumn_dst_day_includes_the_extra_hour(): void
    {
        Carbon::setTestNow(Carbon::parse('2027-10-01T00:00:00Z'));
        $movie = $this->movie();
        $cinema = $this->cinema();
        $first = $this->screening($movie, $cinema, '2027-10-30T21:00:00Z');
        $last = $this->screening($movie, $cinema, '2027-10-31T21:59:00Z');
        $this->screening($movie, $cinema, '2027-10-31T22:00:00Z');
        $this->getJson('/api/screenings?date=2027-10-31')->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonPath('data.0.id', $first->id)
            ->assertJsonPath('data.1.id', $last->id);
    }

    public function test_cinema_movie_filters_and_pagination(): void
    {
        Carbon::setTestNow(Carbon::parse('2027-03-01T00:00:00Z'));
        $movie = $this->movie();
        $cinema = $this->cinema('First cinema');
        $otherCinema = $this->cinema('Second cinema');
        $this->screening($this->movie('Other film'), $cinema, '2027-03-28T10:00:00Z');
        $this->screening($movie, $otherCinema, '2027-03-28T10:00:00Z');
        $first = $this->screening($movie, $cinema, '2027-03-28T10:00:00Z');
        $second = $this->screening($movie, $cinema, '2027-03-28T11:00:00Z');
        $url = '/api/screenings?date=2027-03-28&cinema_id='.$cinema->id.'&movie_id='.$movie->id.'&per_page=1';
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 2)->assertJsonPath('data.0.id', $first->id);
        $this->getJson($url.'&page=2')->assertOk()->assertJsonPath('data.0.id', $second->id);
        $this->getJson('/api/cinemas')->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'First cinema');
    }

    public function test_today_hides_started_screenings_and_past_days_are_empty(): void
    {
        Carbon::setTestNow(Carbon::parse('2027-03-28T10:30:00Z'));
        $movie = $this->movie();
        $cinema = $this->cinema();
        $this->screening($movie, $cinema, '2027-03-28T10:00:00Z');
        $future = $this->screening($movie, $cinema, '2027-03-28T10:31:00Z');
        $this->getJson('/api/screenings')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $future->id);
        $this->getJson('/api/screenings?date=2027-03-27')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_invalid_filters_return_validation_errors(): void
    {
        foreach (['date=not-a-date', 'date=2027-02-30', 'cinema_id=99999999', 'movie_id=9999999999999', 'per_page=0', 'page=-1'] as $query) {
            $this->getJson('/api/screenings?'.$query)->assertUnprocessable();
        }
    }
}
