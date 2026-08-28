@echo off
REM ============================================================================
REM  AgriSphere C++ backend - Windows build script (MinGW-w64 g++)
REM
REM  Requirements: a C++17 compiler. Nothing else - no MySQL SDK, no vcpkg,
REM  no CMake. The only library linked is the Windows socket library.
REM
REM  Usage:  build.bat            (release build)
REM          build.bat debug      (debug build with symbols)
REM ============================================================================
setlocal enabledelayedexpansion

cd /d "%~dp0"

REM --- locate g++ -------------------------------------------------------------
set "CXX="
where g++ >nul 2>nul && set "CXX=g++"
if not defined CXX if exist "C:\mingw64\bin\g++.exe" set "CXX=C:\mingw64\bin\g++.exe"
if not defined CXX if exist "C:\msys64\ucrt64\bin\g++.exe" set "CXX=C:\msys64\ucrt64\bin\g++.exe"
if not defined CXX if exist "C:\msys64\mingw64\bin\g++.exe" set "CXX=C:\msys64\mingw64\bin\g++.exe"

if not defined CXX (
    echo [ERROR] g++ was not found.
    echo         Install MinGW-w64 ^(for example winget install BrechtSanders.WinLibs.POSIX.UCRT^)
    echo         or add your existing g++ to PATH, then run build.bat again.
    exit /b 1
)

echo [1/3] Compiler: %CXX%

if /i "%~1"=="debug" (
    set "FLAGS=-std=c++17 -g -O0 -Wall -Wextra -DDEBUG"
    echo [2/3] Configuration: debug
) else (
    set "FLAGS=-std=c++17 -O2 -Wall -Wextra -DNDEBUG"
    echo [2/3] Configuration: release
)

if not exist "bin" mkdir "bin"

set "SOURCES="
for %%F in (
    src\main.cpp
    src\core\Strings.cpp
    src\core\Json.cpp
    src\core\Sha1.cpp
    src\core\Config.cpp
    src\core\Logger.cpp
    src\db\ResultSet.cpp
    src\db\JsonMapper.cpp
    src\db\MySqlConnection.cpp
    src\db\DatabaseManager.cpp
    src\repositories\UserRepository.cpp
    src\repositories\RegistrationRepository.cpp
    src\repositories\CatalogRepository.cpp
    src\repositories\CartRepository.cpp
    src\repositories\OrderRepository.cpp
    src\repositories\DiscountRepository.cpp
    src\repositories\NotificationRepository.cpp
    src\repositories\MembershipRepository.cpp
    src\repositories\PackageRepository.cpp
    src\observer\DiscountEvent.cpp
    src\observer\DiscountSubject.cpp
    src\observer\MemberNotificationObserver.cpp
    src\observer\BackendLogObserver.cpp
    src\decorator\BaseNotification.cpp
    src\decorator\NotificationDecorator.cpp
    src\decorator\SmsDecorator.cpp
    src\decorator\EmailDecorator.cpp
    src\decorator\PushNotificationDecorator.cpp
    src\decorator\NotificationChain.cpp
    src\facade\PackageFacade.cpp
    src\payment\WalletPaymentMethod.cpp
    src\payment\BkashPayment.cpp
    src\payment\NagadPayment.cpp
    src\payment\CashOnDeliveryPayment.cpp
    src\payment\CardPayment.cpp
    src\payment\PaymentCreator.cpp
    src\payment\PaymentCreatorRegistry.cpp
    src\payment\BkashCreator.cpp
    src\payment\NagadCreator.cpp
    src\payment\CashOnDeliveryCreator.cpp
    src\payment\CardCreator.cpp
    src\strategy\RegularDiscountStrategy.cpp
    src\strategy\PremiumDiscountStrategy.cpp
    src\strategy\BulkDiscountStrategy.cpp
    src\strategy\PremiumBulkDiscountStrategy.cpp
    src\strategy\DiscountContext.cpp
    src\services\DiscountService.cpp
    src\services\MembershipService.cpp
    src\services\NotificationService.cpp
    src\services\AuthService.cpp
    src\services\RegistrationService.cpp
    src\services\CatalogService.cpp
    src\services\CartService.cpp
    src\services\OrderService.cpp
    src\services\FarmerService.cpp
    src\services\AdminService.cpp
    src\http\HttpTypes.cpp
    src\http\Router.cpp
    src\http\HttpServer.cpp
    src\api\ApiRoutes.cpp
) do set "SOURCES=!SOURCES! %%F"

echo [3/3] Building bin\agrisphere_backend.exe ...
REM -static produces a self-contained exe: no MinGW DLLs need to be on PATH.
"%CXX%" %FLAGS% -Iinclude !SOURCES! -o bin\agrisphere_backend.exe -lws2_32 -static -static-libgcc -static-libstdc++
if errorlevel 1 (
    echo.
    echo [ERROR] Build failed.
    exit /b 1
)

echo.
echo [OK] Built bin\agrisphere_backend.exe
echo      Start it with:  start_backend.bat
endlocal
