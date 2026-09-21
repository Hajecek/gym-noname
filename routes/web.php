<?php

declare(strict_types=1);

use App\Controllers\Admin\AdminController;
use App\Controllers\Staff\StaffController;
use App\Controllers\User\DashboardController;
use App\Controllers\User\ProfileController;
use App\Controllers\User\ReservationController;
use App\Controllers\Web\AuthController;
use App\Controllers\Web\PublicController;
use App\Controllers\Web\StripeWebhookController;
use App\Middleware\AdminMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\OwnerMiddleware;
use App\Middleware\StaffMiddleware;

$router = app()->router();

$router->get('/', [PublicController::class, 'home']);
$router->get('/cenik', [PublicController::class, 'pricing']);
$router->get('/faq', [PublicController::class, 'faq']);
$router->get('/kontakt', [PublicController::class, 'contact']);
$router->post('/kontakt', [PublicController::class, 'sendContact']);
$router->get('/zajem', [PublicController::class, 'interest']);
$router->post('/zajem', [PublicController::class, 'signupInterest']);
$router->get('/dokument/{slug}', [PublicController::class, 'legal']);
$router->get('/manifest.json', [PublicController::class, 'manifest']);
$router->get('/avatar/{id}', [PublicController::class, 'avatar']);
$router->get('/uploads/avatars/{file}', [PublicController::class, 'uploadedAvatar']);

$router->get('/registrace', [AuthController::class, 'showRegister'], [GuestMiddleware::class]);
$router->post('/registrace', [AuthController::class, 'register'], [GuestMiddleware::class]);
$router->get('/prihlaseni', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
$router->post('/prihlaseni', [AuthController::class, 'login'], [GuestMiddleware::class]);
$router->post('/odhlaseni', [AuthController::class, 'logout'], [AuthMiddleware::class]);
$router->get('/zapomenute-heslo', [AuthController::class, 'showForgot'], [GuestMiddleware::class]);
$router->post('/zapomenute-heslo', [AuthController::class, 'forgot'], [GuestMiddleware::class]);
$router->get('/obnoveni-hesla', [AuthController::class, 'showReset'], [GuestMiddleware::class]);
$router->post('/obnoveni-hesla', [AuthController::class, 'reset'], [GuestMiddleware::class]);
$router->get('/overeni-emailu', [AuthController::class, 'verifyEmail']);
$router->get('/potvrzeni-emailu', [AuthController::class, 'confirmEmailChange']);

$router->get('/user', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/user/overeni', [DashboardController::class, 'verifyNotice'], [AuthMiddleware::class]);
$router->post('/user/overeni', [DashboardController::class, 'resendVerification'], [AuthMiddleware::class]);
$router->get('/user/vstup', [DashboardController::class, 'access'], [AuthMiddleware::class]);
$router->post('/user/vstup', [DashboardController::class, 'openDoor'], [AuthMiddleware::class]);
$router->get('/user/rezervace', [ReservationController::class, 'index'], [AuthMiddleware::class]);
$router->get('/user/rezervace/dostupnost', [ReservationController::class, 'availability'], [AuthMiddleware::class]);
$router->get('/user/rezervace/kalendar', [ReservationController::class, 'calendar'], [AuthMiddleware::class]);
$router->post('/user/rezervace', [ReservationController::class, 'store'], [AuthMiddleware::class]);
$router->get('/user/rezervace/platba', [ReservationController::class, 'paid'], [AuthMiddleware::class]);
$router->get('/user/rezervace/platba/zruseno', [ReservationController::class, 'checkoutCancel'], [AuthMiddleware::class]);
$router->post('/user/rezervace/{id}/zrusit', [ReservationController::class, 'cancel'], [AuthMiddleware::class]);
$router->get('/user/clenstvi', [ProfileController::class, 'membership'], [AuthMiddleware::class]);
$router->get('/user/profil', [ProfileController::class, 'index'], [AuthMiddleware::class]);
$router->post('/user/profil', [ProfileController::class, 'update'], [AuthMiddleware::class]);
$router->post('/user/profil/heslo', [ProfileController::class, 'password'], [AuthMiddleware::class]);
$router->post('/user/profil/email', [ProfileController::class, 'email'], [AuthMiddleware::class]);
$router->post('/user/profil/avatar', [ProfileController::class, 'avatar'], [AuthMiddleware::class]);
$router->post('/user/profil/avatar/smazat', [ProfileController::class, 'deleteAvatar'], [AuthMiddleware::class]);
$router->post('/user/profil/relace/{id}/odhlasit', [ProfileController::class, 'revokeSession'], [AuthMiddleware::class]);
$router->post('/user/profil/odhlasit-vse', [ProfileController::class, 'logoutAll'], [AuthMiddleware::class]);
$router->post('/user/profil/oznameni', [ProfileController::class, 'notifications'], [AuthMiddleware::class]);
$router->get('/user/profil/export', [ProfileController::class, 'export'], [AuthMiddleware::class]);
$router->post('/user/profil/vymaz', [ProfileController::class, 'requestDeletion'], [AuthMiddleware::class]);
$router->get('/user/zabezpeceni/mfa', [ProfileController::class, 'showMfa'], [AuthMiddleware::class]);
$router->post('/user/zabezpeceni/mfa', [ProfileController::class, 'confirmMfa'], [AuthMiddleware::class]);
$router->post('/user/zabezpeceni/mfa/vypnout', [ProfileController::class, 'disableMfa'], [AuthMiddleware::class]);
$router->get('/user/zabezpeceni/mfa/qr', [ProfileController::class, 'mfaQr'], [AuthMiddleware::class]);
$router->get('/user/zabezpeceni/mfa/kody', [ProfileController::class, 'recoveryCodes'], [AuthMiddleware::class]);

$router->get('/provoz', [StaffController::class, 'index'], [AuthMiddleware::class, StaffMiddleware::class]);
$router->post('/provoz/problem', [StaffController::class, 'issue'], [AuthMiddleware::class, StaffMiddleware::class]);

$router->get('/admin', [AdminController::class, 'dashboard'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->get('/admin/zakaznici', [AdminController::class, 'users'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->get('/admin/zakaznici/{id}', [AdminController::class, 'userShow'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->post('/admin/zakaznici/{id}', [AdminController::class, 'userUpdate'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->post('/admin/zakaznici/{id}/role', [AdminController::class, 'userRole'], [AuthMiddleware::class, OwnerMiddleware::class]);
$router->post('/admin/zakaznici/{id}/clenstvi', [AdminController::class, 'assignMembership'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->get('/admin/rezervace', [AdminController::class, 'reservations'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->post('/admin/rezervace', [AdminController::class, 'createReservation'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->post('/admin/rezervace/blokace', [AdminController::class, 'blockSlot'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->post('/admin/rezervace/provozni-doba', [AdminController::class, 'openingHours'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->post('/admin/rezervace/vyjimka', [AdminController::class, 'exception'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->get('/admin/obsah', [AdminController::class, 'content'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->post('/admin/obsah', [AdminController::class, 'saveContent'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->post('/admin/obsah/faq', [AdminController::class, 'saveFaq'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->get('/admin/clenstvi', [AdminController::class, 'plans'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->post('/admin/clenstvi', [AdminController::class, 'savePlan'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->get('/admin/vstup', [AdminController::class, 'access'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->post('/admin/vstup/test', [AdminController::class, 'testOpen'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->get('/admin/zajem', [AdminController::class, 'interest'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->get('/admin/zajem/export', [AdminController::class, 'exportInterest'], [AuthMiddleware::class, AdminMiddleware::class]);
$router->get('/admin/nastaveni', [AdminController::class, 'settings'], [AuthMiddleware::class, OwnerMiddleware::class]);
$router->post('/admin/nastaveni', [AdminController::class, 'saveSettings'], [AuthMiddleware::class, OwnerMiddleware::class]);

$router->post('/platba/stripe', [StripeWebhookController::class, 'handle']);
