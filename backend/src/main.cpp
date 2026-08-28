

#include "agrisphere/api/ApiRoutes.h"
#include "agrisphere/core/Config.h"
#include "agrisphere/core/Logger.h"
#include "agrisphere/core/Strings.h"
#include "agrisphere/db/DatabaseManager.h"
#include "agrisphere/http/HttpServer.h"
#include "agrisphere/http/Router.h"
#include "agrisphere/payment/PaymentCreatorRegistry.h"

#if defined(_WIN32)
#ifndef WIN32_LEAN_AND_MEAN
#define WIN32_LEAN_AND_MEAN
#endif
#include <windows.h>
#endif

#include <iostream>
#include <string>

namespace {

agri::http::HttpServer* g_server = nullptr;

#if defined(_WIN32)
BOOL WINAPI consoleHandler(DWORD signal) {
    if (signal == CTRL_C_EVENT || signal == CTRL_CLOSE_EVENT || signal == CTRL_BREAK_EVENT) {
        agri::Logger::info("Shutdown requested, stopping the AgriSphere backend...");
        if (g_server) {
            g_server->stop();
        }
        return TRUE;
    }
    return FALSE;
}
#endif

std::string directoryOf(const std::string& path) {
    const std::size_t slash = path.find_last_of("/\\");
    return slash == std::string::npos ? std::string(".") : path.substr(0, slash);
}

} // namespace

int main(int argc, char** argv) {
    // Default: <exe directory>/../config/backend.ini, overridable on the CLI.
    std::string configPath = directoryOf(argv[0]) + "/../config/backend.ini";
    for (int i = 1; i < argc; ++i) {
        const std::string argument = argv[i];
        if ((argument == "--config" || argument == "-c") && i + 1 < argc) {
            configPath = argv[++i];
        } else if (agri::strings::startsWith(argument, "--config=")) {
            configPath = argument.substr(9);
        } else if (argument == "--help" || argument == "-h") {
            std::cout << "AgriSphere C++ backend\n"
                      << "  agrisphere_backend.exe [--config <path to backend.ini>]\n";
            return 0;
        }
    }

    agri::AppConfig config;
    std::string configError;
    const bool loaded = agri::loadConfig(configPath, config, configError);

    agri::Logger::configure(config.server.logFile, agri::LogLevel::Info);
    agri::Logger::info("AgriSphere C++ backend starting");
    if (loaded) {
        agri::Logger::info("Configuration loaded from " + configPath);
    } else {
        agri::Logger::warn(configError);
    }

    try {
        
        // The Singleton is initialised exactly once here. Every repository in
        // the process afterwards reaches MySQL through
        // DatabaseManager::getInstance().
       
        agri::db::DatabaseManager::getInstance().initialize(config.db, config.server.poolSize);
    } catch (const std::exception& error) {
        agri::Logger::error(std::string("Cannot reach MySQL: ") + error.what());
        agri::Logger::error("Start MySQL in the XAMPP Control Panel and check "
                            "backend/config/backend.ini, then run this again.");
        return 1;
    }

   
    agri::payment::registerPaymentCreators();

    agri::http::Router router;
    agri::api::registerRoutes(router, config);

    agri::http::HttpServer server(config.server, router);
    g_server = &server;
#if defined(_WIN32)
    SetConsoleCtrlHandler(consoleHandler, TRUE);
#endif

    int exitCode = 0;
    try {
        server.run();
    } catch (const std::exception& error) {
        agri::Logger::error(std::string("HTTP server stopped: ") + error.what());
        exitCode = 1;
    }

    g_server = nullptr;
    agri::db::DatabaseManager::getInstance().shutdown();
    agri::Logger::info("AgriSphere C++ backend stopped");
    return exitCode;
}
