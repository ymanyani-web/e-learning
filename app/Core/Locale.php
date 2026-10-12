<?php

declare(strict_types=1);

namespace App\Core;

class Locale
{
    private const STRINGS = [
        'en' => [
            'overview' => 'Overview',
            'students' => 'Students',
            'teachers' => 'Teachers',
            'classes' => 'Classes',
            'subjects' => 'Subjects',
            'settings' => 'Settings',
            'logout' => 'Log out',
            'save_changes' => 'Save changes',
        ],
        'fr' => [
            'overview' => 'Aperçu',
            'students' => 'Étudiants',
            'teachers' => 'Professeurs',
            'classes' => 'Classes',
            'subjects' => 'Matières',
            'settings' => 'Paramètres',
            'logout' => 'Déconnexion',
            'save_changes' => 'Enregistrer les modifications',
        ],
    ];

    public static function language(): string
    {
        $language = Session::get('locale', 'en');
        return in_array($language, ['en', 'fr'], true) ? $language : 'en';
    }

    public static function setLanguage(string $language): void
    {
        Session::put('locale', in_array($language, ['en', 'fr'], true) ? $language : 'en');
    }

    public static function text(string $key): string
    {
        return self::STRINGS[self::language()][$key] ?? self::STRINGS['en'][$key] ?? $key;
    }
}
