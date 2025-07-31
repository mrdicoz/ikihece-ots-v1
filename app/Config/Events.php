<?php

namespace Config;

use CodeIgniter\Events\Events;
use CodeIgniter\Exceptions\FrameworkException;
use CodeIgniter\HotReloader\HotReloader;
use App\Listeners\LogListener; // Listener'ımızı dahil ediyoruz

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

Events::on('pre_system', static function (): void {
    if (ENVIRONMENT !== 'testing') {
        if (ini_get('zlib.output_compression')) {
            throw FrameworkException::forEnabledZlibOutputCompression();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        ob_start(static fn ($buffer) => $buffer);
    }

    /*
     * --------------------------------------------------------------------
     * Debug Toolbar Listeners.
     * --------------------------------------------------------------------
     * If you delete, they will no longer be collected.
     */
    if (CI_DEBUG && ! is_cli()) {
        Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
        service('toolbar')->respond();
        // Hot Reload route - for framework use on the hot reloader.
        if (ENVIRONMENT === 'development') {
            service('routes')->get('__hot-reload', static function (): void {
                (new HotReloader())->run();
            });
        }
    }
});


/*
 * --------------------------------------------------------------------
 * Events
 * --------------------------------------------------------------------
 * Maps a specific event name to a class and method that will be called
 * when that event is triggered.
 *
 * E.g.:
 * Events::on('pre_system', 'MyClass::preSystem');
 */

Events::on('pre_system', static function () {
    if (ENVIRONMENT !== 'testing') {
        if (ini_get('zlib.output_compression')) {
            throw FrameworkException::forEnabledZlibOutputCompression();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        ob_start(static function ($buffer) {
            return $buffer;
        });
    }

    /*
     * --------------------------------------------------------------------
     * Debug Toolbar Listeners.
     * --------------------------------------------------------------------
     * If you delete, they will no longer be collected.
     */
    if (CI_DEBUG && ! is_cli()) {
        Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
        Services::toolbar()->respond();
    }
});



// BİZİM LOGLAMA OLAYLARIMIZ
// Bir olay tetiklendiğinde hangi sınıfın hangi metodunun çalışacağını belirtiyoruz.
Events::on('user.created', [new LogListener(), 'handleUserCreation']);
Events::on('user.updated', [new LogListener(), 'handleUserUpdate']);   // YENİ
Events::on('user.deleted', [new LogListener(), 'handleUserDeletion']); // YENİ

// YENİ EKLENEN ÖĞRENCİ OLAYLARI
Events::on('student.created', [new LogListener(), 'handleStudentCreation']);
Events::on('student.updated', [new LogListener(), 'handleStudentUpdate']);
Events::on('student.deleted', [new LogListener(), 'handleStudentDeletion']);

// EKLENEN DERS OLAYLARI
// Ders programı güncellendiğinde bildirim göndermek için
Events::on('schedule.updated', static function($userId, $title, $body) {
    $listener = new \App\Listeners\NotificationListener();
    $listener->handleScheduleChange($userId, $title, $body);
});

/**
 * Duyuru yayınlandığında bildirim gönderir.
 */
Events::on('announcement.published', [new \App\Listeners\NotificationListener(), 'handleAnnouncementPublished']);