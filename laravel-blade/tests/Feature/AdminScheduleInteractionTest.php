<?php

namespace Tests\Feature;

use App\Models\Comic;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminScheduleInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_schedule_crud_is_reflected_on_public_schedule(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $comic = Comic::factory()->create(['title' => 'Schedule Test Comic']);

        $this->actingAs($admin)
            ->from(route('admin.schedules.index'))
            ->post(route('admin.schedules.store'), [
                'comic_id' => $comic->id,
                'day_of_week' => 2,
                'release_time' => '20:30',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.schedules.index'));

        $schedule = Schedule::where('comic_id', $comic->id)->where('day_of_week', 2)->firstOrFail();

        $this->get(route('schedule', ['day' => 2]))
            ->assertOk()
            ->assertSee('Schedule Test Comic');

        $this->actingAs($admin)
            ->from(route('admin.schedules.index'))
            ->put(route('admin.schedules.update', $schedule), [
                'comic_id' => $comic->id,
                'day_of_week' => 3,
                'release_time' => '21:15',
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.schedules.index'));

        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'day_of_week' => 3,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.schedules.index'))
            ->delete(route('admin.schedules.destroy', $schedule))
            ->assertRedirect(route('admin.schedules.index'));

        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }
}
