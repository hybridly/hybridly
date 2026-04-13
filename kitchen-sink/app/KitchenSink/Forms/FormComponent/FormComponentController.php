<?php

namespace App\KitchenSink\Forms\FormComponent;

use Carbon\CarbonImmutable;
use Discovery\Routing\Get;
use Discovery\Routing\Post;
use Discovery\Routing\Prefix;
use Discovery\Routing\Web;
use Hybridly\HybridResponse;

use function Hybridly\properties;
use function Hybridly\view;

#[Web, Prefix(uri: '/kitchen-sink/forms/form-component', name: 'kitchen-sink.forms.form-component')]
final class FormComponentController
{
    #[Get('/', name: 'index')]
    public function __invoke(): HybridResponse
    {
        return view('kitchen-sink::forms.form-component.index', [
            'spellRiskOptions' => SpellRiskLevel::values(),
            'lastSpellDiscoveryAt' => CarbonImmutable::now(),
        ]);
    }

    #[Post('/spell-discovery', name: 'spell-discovery')]
    public function submitSpellDiscovery(SpellDiscoveryRequest $request_data): HybridResponse
    {
        return properties([
            'lastSpellDiscoveryAt' => CarbonImmutable::now(),
            'form' => $request_data,
        ]);
    }
}
