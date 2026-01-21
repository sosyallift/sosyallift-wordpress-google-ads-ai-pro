sosyallift-ai-pro/
├── sosyallift-ai-pro.php
├── uninstall.php
├── README.md
├── ROADMAP.md
├── core/
│   ├── Bootstrap.php
│   ├── Activator.php
│   ├── Deactivator.php
│   ├── Uninstaller.php
│   ├── Security.php
│   ├── Validator.php
│   ├── Logger.php
│   ├── ApiClient.php
│   ├── LicenseManager.php
│   ├── CronManager.php
│   ├── CacheManager.php
│   └── EventDispatcher.php
├── includes/
│   ├── traits/
│   │   ├── Singleton.php
│   │   ├── AjaxHandler.php
│   │   └── Hookable.php
│   ├── interfaces/
│   │   ├── ModuleInterface.php
│   │   ├── AnalyzerInterface.php
│   │   └── ScorerInterface.php
│   └── exceptions/
│       ├── ApiException.php
│       ├── ValidationException.php
│       └── LicenseException.php
├── modules/
│   ├── GoogleAds/
│   │   ├── ApiHandler.php
│   │   ├── ReportFetcher.php
│   │   ├── KeywordAnalyzer.php
│   │   ├── NegativeGenerator.php
│   │   └── BidOptimizer.php
│   ├── SEO/
│   │   ├── OnPageAnalyzer.php
│   │   ├── CompetitorTracker.php
│   │   ├── RankMonitor.php
│   │   └── ContentScorer.php
│   ├── Intent/
│   │   ├── IntentDetector.php
│   │   ├── QueryClassifier.php
│   │   ├── PurchasePredictor.php
│   │   └── BehaviorAnalyzer.php
│   └── Intelligence/
│       ├── PatternRecognizer.php
│       ├── AnomalyDetector.php
│       ├── RecommendationEngine.php
│       └── PredictiveModel.php
├── admin/
│   ├── Admin.php
│   ├── Dashboard.php
│   ├── Settings.php
│   ├── Analytics.php
│   ├── Reports.php
│   ├── Alerts.php
│   ├── License.php
│   └── views/
│       ├── dashboard/
│       │   ├── overview.php
│       │   ├── performance.php
│       │   ├── keywords.php
│       │   └── recommendations.php
│       └── partials/
│           ├── header.php
│           ├── sidebar.php
│           └── modals/
├── api/
│   ├── RestApi.php
│   ├── endpoints/
│   │   ├── v1/
│   │   │   ├── Keywords.php
│   │   │   ├── Analytics.php
│   │   │   ├── Reports.php
│   │   │   └── Webhooks.php
│   │   └── v2/
│   ├── middleware/
│   │   ├── AuthMiddleware.php
│   │   ├── RateLimiter.php
│   │   └── ValidationMiddleware.php
│   └── responses/
│       ├── SuccessResponse.php
│       ├── ErrorResponse.php
│       └── PaginatedResponse.php
├── database/
│   ├── migrations/
│   │   ├── 0001_initial_schema.php
│   │   ├── 0002_add_intent_tables.php
│   │   └── 0003_add_competitor_data.php
│   ├── models/
│   │   ├── Keyword.php
│   │   ├── Campaign.php
│   │   ├── Intent.php
│   │   ├── Score.php
│   │   └── Alert.php
│   └── queries/
│       ├── KeywordQueries.php
│       ├── AnalyticsQueries.php
│       └── ReportQueries.php
├── assets/
│   ├── js/
│   │   ├── admin/
│   │   │   ├── app.js
│   │   │   ├── dashboard.js
│   │   │   ├── charts.js
│   │   │   ├── realtime.js
│   │   │   └── notifications.js
│   │   ├── vendor/
│   │   │   ├── chart.min.js
│   │   │   ├── apexcharts.min.js
│   │   │   ├── datatables.min.js
│   │   │   └── select2.min.js
│   │   └── lib/
│   │       ├── utils.js
│   │       ├── api.js
│   │       └── analytics.js
│   ├── css/
│   │   ├── admin/
│   │   │   ├── main.css
│   │   │   ├── dashboard.css
│   │   │   ├── dark-mode.css
│   │   │   └── responsive.css
│   │   └── vendor/
│   │       ├── datatables.css
│   │       └── select2.css
│   ├── scss/
│   │   ├── variables.scss
│   │   ├── mixins.scss
│   │   └── components/
│   └── images/
│       ├── icons/
│       └── sprites/
├── cli/
│   ├── commands/
│   │   ├── SyncCommand.php
│   │   ├── AnalyzeCommand.php
│   │   ├── ReportCommand.php
│   │   └── TestCommand.php
│   └── Kernel.php
├── vendor/
│   └── (composer dependencies)
├── logs/
│   ├── debug.log
│   ├── error.log
│   └── api.log
├── cache/
│   ├── queries/
│   └── responses/
├── tests/
│   ├── unit/
│   ├── integration/
│   └── acceptance/
├── config/
│   ├── constants.php
│   ├── defaults.php
│   ├── permissions.php
│   └── services.php
├── templates/
│   ├── emails/
│   │   ├── alert.html
│   │   ├── report.html
│   │   └── license.html
│   └── exports/
│       ├── csv-template.php
│       └── pdf-template.php
└── languages/
    ├── sosyallift-ai-pro-tr_TR.po
    ├── sosyallift-ai-pro-en_US.po
    └── sosyallift-ai-pro-de_DE.po