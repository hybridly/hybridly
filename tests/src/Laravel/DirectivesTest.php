<?php

use Hybridly\View\Payload;
use Hybridly\View\View;
use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    test()->directives = Blade::getCustomDirectives();
});

it('adds a hybridly directive', function () {
    expect(test()->directives['hybridly']())
        ->toBe('<div id="root" class="" data-payload="{{ json_encode($payload) }}"></div>');
});

it('supports changing the wrapper element', function () {
    expect(test()->directives['hybridly']('element: "main"'))
        ->toBe('<main id="root" class="" data-payload="{{ json_encode($payload) }}"></main>');
});

it("supports changing the wrapper element's id", function () {
    expect(test()->directives['hybridly']('id: "app"'))
        ->toBe('<div id="app" class="" data-payload="{{ json_encode($payload) }}"></div>');
});

it("supports changing the wrapper element's class", function () {
    expect(test()->directives['hybridly']('class: "h-full"'))
        ->toBe('<div id="root" class="h-full" data-payload="{{ json_encode($payload) }}"></div>');
});

it('renders encoded payload in the data-payload attribute', function () {
    $payload = new Payload(
        view: new View('users.edit', ['user' => 'Makise Kurisu']),
        url: 'https://localhost/',
        version: 'abc123',
        dialog: null,
    );

    $php = test()->directives['hybridly']();
    $html = Blade::render($php, ['payload' => $payload], true);

    expect($html)->toBe(trim(<<<HTML
        <div id="root" class="" data-payload="{&quot;view&quot;:{&quot;component&quot;:&quot;users.edit&quot;,&quot;properties&quot;:{&quot;user&quot;:&quot;Makise Kurisu&quot;}},&quot;url&quot;:&quot;https:\/\/localhost\/&quot;,&quot;version&quot;:&quot;abc123&quot;,&quot;dialog&quot;:null}"></div>
    HTML));
});
