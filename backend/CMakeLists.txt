# =============================================================================
#  AgriSphere C++ backend
#
#  build.bat is the simplest way to build on Windows; this file is provided for
#  people who prefer CMake or an IDE (Visual Studio, CLion, VS Code).
#
#    cmake -S . -B build -G "MinGW Makefiles"
#    cmake --build build --config Release
#
#  There are no third-party dependencies: the MySQL client protocol is
#  implemented in src/db/MySqlConnection.cpp and the only library linked is the
#  Windows socket library.
# =============================================================================
cmake_minimum_required(VERSION 3.15)
project(agrisphere_backend CXX)

set(CMAKE_CXX_STANDARD 17)
set(CMAKE_CXX_STANDARD_REQUIRED ON)
set(CMAKE_RUNTIME_OUTPUT_DIRECTORY ${CMAKE_CURRENT_SOURCE_DIR}/bin)

add_executable(agrisphere_backend
    src/main.cpp

    src/core/Strings.cpp
    src/core/Json.cpp
    src/core/Sha1.cpp
    src/core/Config.cpp
    src/core/Logger.cpp

    src/db/ResultSet.cpp
    src/db/JsonMapper.cpp
    src/db/MySqlConnection.cpp
    src/db/DatabaseManager.cpp

    src/repositories/UserRepository.cpp
    src/repositories/RegistrationRepository.cpp
    src/repositories/CatalogRepository.cpp
    src/repositories/CartRepository.cpp
    src/repositories/OrderRepository.cpp
    src/repositories/DiscountRepository.cpp
    src/repositories/NotificationRepository.cpp
    src/repositories/MembershipRepository.cpp
    src/repositories/PackageRepository.cpp

    # Observer pattern
    src/observer/DiscountEvent.cpp
    src/observer/DiscountSubject.cpp
    src/observer/MemberNotificationObserver.cpp
    src/observer/BackendLogObserver.cpp

    # Decorator pattern (notification delivery)
    src/decorator/BaseNotification.cpp
    src/decorator/NotificationDecorator.cpp
    src/decorator/SmsDecorator.cpp
    src/decorator/EmailDecorator.cpp
    src/decorator/PushNotificationDecorator.cpp
    src/decorator/NotificationChain.cpp

    # Facade pattern (product packages)
    src/facade/PackageFacade.cpp

    # Factory Method pattern (payment handling)
    src/payment/WalletPaymentMethod.cpp
    src/payment/BkashPayment.cpp
    src/payment/NagadPayment.cpp
    src/payment/CashOnDeliveryPayment.cpp
    src/payment/CardPayment.cpp
    src/payment/PaymentCreator.cpp
    src/payment/PaymentCreatorRegistry.cpp
    src/payment/BkashCreator.cpp
    src/payment/NagadCreator.cpp
    src/payment/CashOnDeliveryCreator.cpp
    src/payment/CardCreator.cpp

    # Strategy pattern (customer discount rules)
    src/strategy/RegularDiscountStrategy.cpp
    src/strategy/PremiumDiscountStrategy.cpp
    src/strategy/BulkDiscountStrategy.cpp
    src/strategy/PremiumBulkDiscountStrategy.cpp
    src/strategy/DiscountContext.cpp

    src/services/DiscountService.cpp
    src/services/MembershipService.cpp
    src/services/NotificationService.cpp
    src/services/AuthService.cpp
    src/services/RegistrationService.cpp
    src/services/CatalogService.cpp
    src/services/CartService.cpp
    src/services/OrderService.cpp
    src/services/FarmerService.cpp
    src/services/AdminService.cpp

    src/http/HttpTypes.cpp
    src/http/Router.cpp
    src/http/HttpServer.cpp

    src/api/ApiRoutes.cpp
)

target_include_directories(agrisphere_backend PRIVATE include)

if(WIN32)
    target_link_libraries(agrisphere_backend PRIVATE ws2_32)
endif()

find_package(Threads REQUIRED)
target_link_libraries(agrisphere_backend PRIVATE Threads::Threads)
