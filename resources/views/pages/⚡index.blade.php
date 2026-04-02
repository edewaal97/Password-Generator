<?php

use Livewire\Attributes\Session;
use Livewire\Attributes\Validate;
use Livewire\Component;
use App\Enums\OptionGroup;
use Livewire\Attributes\Title;

new #[Title('Password Generator')] class extends Component {
    #[Session]
    public OptionGroup $word = OptionGroup::ZEROORMORE;
    #[Session]
    public OptionGroup $capital = OptionGroup::ZEROORMORE;
    #[Session]
    public OptionGroup $number = OptionGroup::ONE;
    #[Session]
    public OptionGroup $special = OptionGroup::ONE;
    #[Session]
    public bool $characterBetweenItems = true;
    #[Session]
    public string $chosenCharacterBetweenItems = "-";
    #[Session]
    #[Validate('lte:maxlength', message: "De minimale lengte moet lager zijn dat de maximale lengte.")]
    public int $minlength = 25;
    #[Session]
    #[Validate('gte:minlength', message: "De maximale lengte moet hoger zijn dat de minimale lengte.")]
    public int $maxlength = 40;

    public array $required = [];

    public array $optional = [];

    public array $passwords = [];

    private array $specialCharacters = [
        '@', '#', '€', '&', '-', '_', '(', ')', '=', '%', '*', ':', '/', '!', '?', '+', ',', '.',
        ';', '{', '}', '[', ']', '<', '>', '\\', '|', '"', '\'',
    ];

    private array $wordlist;

    public function mount(): void
    {
        $this->generate();
    }

    public function updated($property): void
    {
        if (in_array($property, [
            'word', 'capital', 'number', 'special',
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

        $options = [
            'word' => $this->word,
            'capital' => $this->capital,
            'number' => $this->number,
            'special' => $this->special,
        ];

        foreach ($options as $option => $value) {
            if ($value instanceof OptionGroup) {
                if (in_array($value, [OptionGroup::ONE, OptionGroup::ONEORMORE])) {
                    $this->required[] = $option;
                }

                if (in_array($value, [OptionGroup::ONEORMORE, OptionGroup::ZEROORMORE])) {
                    $this->optional[] = $option;
                }
            }
        }

        if (empty($this->optional)) {
            $this->optional[] = 'word';
            $this->word = $options['word'] == OptionGroup::ONE
                ? OptionGroup::ONEORMORE
                : OptionGroup::ZEROORMORE;
        }

        $this->validate();
    }

    public function resetSession(): void
    {
        $this->word = OptionGroup::ZEROORMORE;
        $this->capital = OptionGroup::ZEROORMORE;
        $this->number = OptionGroup::ONE;
        $this->characterBetweenItems = true;
        $this->chosenCharacterBetweenItems = '-';
        $this->minlength = 25;
        $this->maxlength = 40;

        $this->generate();
    }

    public function generatePart($array): string
    {
        $wordlist = $this->getWordlist();

        $specialCharacters = $this->specialCharacters;

        return match ($array) {
            'word' => $this->getRandom($wordlist),
            'capital' => ucfirst($this->getRandom($wordlist)),
            'number' => random_int(0, 999),
            'special' => $this->getRandom($specialCharacters),
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
            $attempts = 0;
            while (strlen($this->implodePassword($passwordArray)) < $this->minlength) {
                if ($attempts > 30) {
                    break;
                }

                $randomItem = array_rand($this->optional);

                $part = $this->generatePart($this->optional[$randomItem]);

                if (strlen($this->implodePassword($passwordArray) . $part) <= $this->maxlength) {
                    $passwordArray[] = $part;
                }

                $attempts++;
            }
        }

        shuffle($passwordArray);

        return $this->implodePassword($passwordArray);
    }

    private function getRandom(array $list): string
    {
        if (empty($list)) {
            return '';
        }

        $random = random_int(0, count($list) - 1);
        return trim($list[$random]);
    }

    private function getWordlist(): array
    {
        if (empty($this->wordlist)) {
            $this->wordlist = Cache::remember('wordlist', now()->addDay(), function () {
                $files = [];
                $flags = FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES;
                $filenames = glob(storage_path("app/wordlists/*.txt"));
                foreach ($filenames as $filename) {
                    $files[] = file($filename, $flags);
                }
                return array_merge(...$files);
            });
        }

        return $this->wordlist;
    }
};
?>

<div class="flex flex-wrap justify-center gap-x-4 gap-y-12 my-6">

    <div class="grow shrink basis-md max-w-xl space-y-4">
        @foreach($passwords as $password)
            <flux:input.group>
                <flux:input :value="$password" class="font-mono" input:class="text-white!" readonly copyable/>
                <flux:input.group.suffix><span class="min-w-[2ch] text-center"><?= strlen($password) ?></span>
                </flux:input.group.suffix>
            </flux:input.group>
        @endforeach
    </div>

    <div class="grow shrink basis-xl max-w-xl space-y-6">
        <flux:card>

            <flux:fieldset>
                <flux:legend>Opties</flux:legend>

                <div class="space-y-4">
                    <flux:radio.group wire:model.live="word"
                                      label="Woorden"
                                      variant="segmented"
                                      size="sm">
                        @foreach(OptionGroup::cases() as $case)
                            <flux:radio :label="$case->label()" :value="$case->value"/>
                        @endforeach
                    </flux:radio.group>

                    <flux:radio.group wire:model.live="capital"
                                      label="Woorden met hoofdletters"
                                      variant="segmented"
                                      size="sm">
                        @foreach(OptionGroup::cases() as $case)
                            <flux:radio :label="$case->label()" :value="$case->value"/>
                        @endforeach
                    </flux:radio.group>

                    <flux:separator variant="subtle"/>

                    <flux:radio.group wire:model.live="number"
                                      label="Getallen"
                                      variant="segmented"
                                      size="sm">
                        @foreach(OptionGroup::cases() as $case)
                            <flux:radio :label="$case->label()" :value="$case->value"/>
                        @endforeach
                    </flux:radio.group>

                    <flux:separator variant="subtle"/>

                    <flux:radio.group wire:model.live="special"
                                      label="Speciaal karakter"
                                      :description="implode(' ', $this->specialCharacters)"
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
                        <flux:error></flux:error>
                    </flux:input.group>
                    <flux:error name="minlength"/>

                    <flux:input.group>
                        <flux:input.group.prefix>Max.</flux:input.group.prefix>
                        <flux:input wire:model.live.debounce.300ms="maxlength" type="number"/>
                        <flux:input.group.suffix>karakters</flux:input.group.suffix>
                    </flux:input.group>
                    <flux:error name="maxlength"/>

                    <flux:separator variant="subtle"/>

                    <flux:button wire:click="generate()">Regenerate</flux:button>

                    <flux:button wire:click="resetSession()">Reset to Default</flux:button>

                </div>
            </flux:fieldset>

        </flux:card>
    </div>

</div>
