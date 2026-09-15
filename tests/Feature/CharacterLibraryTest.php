<?php

use App\Livewire\StoryStudio;
use App\Models\Character;
use App\Models\User;
use Livewire\Livewire;

test('authenticated user can create, update, and delete character sheets', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create character
    Livewire::test(StoryStudio::class)
        ->set('characterName', 'Naya Arunika')
        ->set('characterGender', 'female')
        ->set('characterAge', 23)
        ->set('characterPromptKeywords', '23yo Indonesian woman, Sundanese Javanese, oval face, dark espresso bob')
        ->set('characterAppearance', 'Calm and confident beauty creator')
        ->call('saveCharacter')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('characters', [
        'name' => 'Naya Arunika',
        'gender' => 'female',
        'age' => 23,
    ]);

    $character = Character::where('name', 'Naya Arunika')->first();

    // Edit character
    Livewire::test(StoryStudio::class)
        ->call('editCharacter', $character->id)
        ->set('characterName', 'Naya Arunika Updated')
        ->call('saveCharacter')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('characters', [
        'id' => $character->id,
        'name' => 'Naya Arunika Updated',
    ]);

    // Delete character
    Livewire::test(StoryStudio::class)
        ->call('deleteCharacter', $character->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('characters', [
        'id' => $character->id,
    ]);
});
