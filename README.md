# PHPStan Livewire
Recognize Livewire computed properties

### Installation
First install the package via composer:
```
composer require diverently/phpstan-livewire --dev
```

Then add the following to your phpstan.neon:
```
includes:
    - ./vendor/diverently/phpstan-livewire/extension.neon
