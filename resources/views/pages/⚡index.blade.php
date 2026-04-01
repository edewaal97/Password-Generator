<?php

use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use App\Enums\OptionGroup;

new class extends Component {
    public array $opt = [
        "word" => OptionGroup::ZEROORMORE,
        "capital" => OptionGroup::ZEROORMORE,
        "number" => OptionGroup::ONEORMORE,
    ];

    public bool $characterBetweenItems = true;
    public string $chosenCharacterBetweenItems = "-";

    public array $required = [];

    public array $optional = [];

    #[Url]
    public int $minlength = 25;

    #[Url]
    public int $maxlength = 40;

    public array $passwords = [];

    public function mount(): void
    {
        $this->generate();
    }

    public function updated($property): void
    {
        if (in_array($property, [
            'opt.word', 'opt.capital', 'opt.number',
            'characterBetweenItems',
            'chosenCharacterBetweenItems',
            'minlength',
            'maxlength'
        ])) {
            $this->generate();
        }
    }

    public function generate($count = 8): void
    {
        $this->internalSettings();

        $this->passwords = [];
        for ($i = 0; $i < $count; $i++) {
            $this->passwords[] = $this->generatePassword();
        }
    }

    public function internalSettings(): void
    {
        $this->required = [];
        $this->optional = [];

        foreach ($this->opt as $option => $value) {
            if ($value instanceof OptionGroup) {
                if (in_array($value, [OptionGroup::ONE, OptionGroup::ONEORMORE])) {
                    $this->required[] = $option;
                }

                if (in_array($value, [OptionGroup::ONEORMORE, OptionGroup::ZEROORMORE])) {
                    $this->optional[] = $option;
                }
            }

            if (is_bool($value)) {
                if ($value) {
                    $this->required[] = $option;
                    $this->optional[] = $option;
                }
            }
        }

        if (empty($this->optional)) {
            $this->optional[] = 'word';
            $this->opt['word'] = $this->opt['word'] == OptionGroup::ONE
                ? OptionGroup::ONEORMORE
                : OptionGroup::ZEROORMORE;
        }

        if ($this->minlength > $this->maxlength) {
            $this->minlength = $this->maxlength;
        }
    }

    public function generatePart($array): string
    {
        $wordlist = [
            'lorem',
            'ipsum',
            'dolor',
            'sit',
            'amit',
        ];

        $specialCharacters = ['[', ']', ';', ',', '.', '-', '#', '&',];

        return match ($array) {
            'word' => $this->getRandom($wordlist),
            'capital' => ucfirst($this->getRandom($wordlist)),
            'number' => random_int(0, 9),
        };
    }

    public function implodePassword($passwordArray): string
    {
        return $this->characterBetweenItems
            ? implode($this->chosenCharacterBetweenItems, $passwordArray)
            : implode($passwordArray);
    }

    public function generatePassword(): string
    {
        $required = $this->required;

        $passwordArray = [];

        while (count($required) !== 0) {
            $randomItem = array_rand($required);

            $part = $this->generatePart($required[$randomItem]);

            unset($required[$randomItem]);

            $passwordArray[] = $part;
        }

        if (!empty($this->optional)) {
            while (strlen($this->implodePassword($passwordArray)) < $this->minlength) {
                $randomItem = array_rand($this->optional);

                $part = $this->generatePart($this->optional[$randomItem]);

                if (strlen($this->implodePassword($passwordArray) . $part) <= $this->maxlength) {
                    $passwordArray[] = $part;
                }
            }
        }

        shuffle($passwordArray);

        return $this->implodePassword($passwordArray);
    }

    private function getRandom(array $list): string
    {
        $random = random_int(0, count($list) - 1);
        return trim($list[$random]);
    }
};
?>

<div class="flex flex-wrap justify-center gap-x-4 gap-y-12 my-6">

    <div class="grow shrink basis-md max-w-xl space-y-4">
        @foreach($passwords as $password)
            <flux:input.group>
                <flux:input :value="$password" class="font-mono" readonly copyable/>
                <flux:input.group.suffix><span class="min-w-[2ch] text-center"><?= strlen($password) ?></span></flux:input.group.suffix>
            </flux:input.group>
        @endforeach
    </div>

    <div class="grow shrink basis-xl max-w-xl space-y-6">
        <flux:card>

            <flux:fieldset>
                <flux:legend>Opties</flux:legend>

                <div class="space-y-4">
                    <flux:radio.group wire:model.live="opt.word"
                                      label="Woorden"
                                      variant="segmented"
                                      size="sm">
                        @foreach(OptionGroup::cases() as $case)
                            <flux:radio :label="$case->label()" :value="$case->value"/>
                        @endforeach
                    </flux:radio.group>

                    <flux:radio.group wire:model.live="opt.capital"
                                      label="Woorden met hoofdletters"
                                      variant="segmented"
                                      size="sm">
                        @foreach(OptionGroup::cases() as $case)
                            <flux:radio :label="$case->label()" :value="$case->value"/>
                        @endforeach
                    </flux:radio.group>

                    <flux:separator variant="subtle"/>

                    <flux:radio.group wire:model.live="opt.number"
                                      label="Getallen"
                                      variant="segmented"
                                      size="sm">
                        @foreach(OptionGroup::cases() as $case)
                            <flux:radio :label="$case->label()" :value="$case->value"/>
                        @endforeach
                    </flux:radio.group>

                    <flux:separator variant="subtle"/>

                    <flux:switch wire:model.live="characterBetweenItems"
                                 label="Karakter tussen items"/>

                    <div x-show="$wire.characterBetweenItems" x-transition>
                        <flux:input wire:model.live="chosenCharacterBetweenItems"
                                    label="Welk karakter?"
                                    description="Kies welk karakter tussen de items in moet staan"/>
                    </div>

                    <flux:separator variant="subtle"/>

                    <flux:label>Lengte:</flux:label>
                    <flux:input.group>
                        <flux:input.group.prefix>Min.</flux:input.group.prefix>
                        <flux:input wire:model.live.debounce.300ms="minlength" type="number"/>
                        <flux:input.group.suffix>karakters</flux:input.group.suffix>
                    </flux:input.group>

                    <flux:input.group>
                        <flux:input.group.prefix>Max.</flux:input.group.prefix>
                        <flux:input wire:model.live.debounce.300ms="maxlength" type="number" />
                        <flux:input.group.suffix>karakters</flux:input.group.suffix>
                    </flux:input.group>

                    <flux:separator variant="subtle"/>

                    <flux:button wire:click="generate()">Regenerate</flux:button>

                </div>
            </flux:fieldset>

        </flux:card>
    </div>

</div>
