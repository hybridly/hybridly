<?php

use Illuminate\Support\Facades\Blade;
use Monolikit\View\Payload;
use Monolikit\View\View;

beforeEach(function () {
    test()->directives = Blade::getCustomDirectives();
});

it('adds a monolikit directive', function () {
    expect(test()->directives['monolikit']())
        ->toBe('<div id="root" class="" data-payload="{{ json_encode($payload) }}"></div>');
});

it('supports changing the wrapper element id', function () {
    expect(test()->directives['monolikit']('id: "app"'))
        ->toBe('<div id="app" class="" data-payload="{{ json_encode($payload) }}"></div>');
});

it('supports changing the wrapper element class', function () {
    expect(test()->directives['monolikit']('class: "h-full"'))
        ->toBe('<div id="root" class="h-full" data-payload="{{ json_encode($payload) }}"></div>');
});

it('renders encoded payload in the data-payload attribute', function () {
    $payload = new Payload(
        view: new View('users.edit', ['user' => 'Makise Kurisu']),
        url: 'https://localhost/',
        version: 'abc123',
        dialog: null,
    );

    $php = test()->directives['monolikit']();
    $html = Blade::render($php, ['payload' => $payload], true);

    expect($html)->toBe(trim(<<<HTML
        <div id="root" class="" data-payload="{&quot;view&quot;:{&quot;component&quot;:&quot;users.edit&quot;,&quot;properties&quot;:{&quot;user&quot;:&quot;Makise Kurisu&quot;}},&quot;url&quot;:&quot;https:\/\/localhost\/&quot;,&quot;version&quot;:&quot;abc123&quot;,&quot;dialog&quot;:null}"></div>
    HTML));
});
