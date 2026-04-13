<?php

namespace App\KitchenSink\Forms\Validation;

use Carbon\CarbonImmutable;
use Discovery\Routing\Get;
use Discovery\Routing\Post;
use Discovery\Routing\Prefix;
use Discovery\Routing\Web;
use Hybridly\HybridResponse;

use function Hybridly\properties;
use function Hybridly\view;

#[Web, Prefix(uri: '/kitchen-sink/forms/validation', name: 'kitchen-sink.forms.validation')]
final class ValidationController
{
    #[Get('/', name: 'index')]
    public function __invoke(): HybridResponse
    {
        return view('kitchen-sink::forms.validation.index', [
            'speciesOptions' => Species::values(),
            'moralAlignmentOptions' => MoralAlignment::values(),
            'spellRiskOptions' => SpellRiskLevel::values(),
            'lastMageRegistrationAt' => CarbonImmutable::now(),
            'lastSpellDiscoveryAt' => CarbonImmutable::now(),
        ]);
    }

    #[Post('/mage-registration', name: 'mage-registration')]
    public function submitMageRegistration(MageRegistrationRequest $request_data): HybridResponse
    {
        return properties([
            'lastMageRegistrationAt' => CarbonImmutable::now(),
        ]);
    }

    #[Post('/spell-discovery', name: 'spell-discovery')]
    public function submitSpellDiscovery(SpellDiscoveryRequest $request_data): HybridResponse
    {
        return properties([
            'lastSpellDiscoveryAt' => CarbonImmutable::now(),
        ]);
    }

    #[Get('/dialog', name: 'dialog')]
    public function dialog(): HybridResponse
    {
        return view('kitchen-sink::forms.validation.dialog', [
            'spellRiskOptions' => SpellRiskLevel::values(),
        ])->configureDialog(
            baseUrl: action(self::class, absolute: false),
        );
    }
}
