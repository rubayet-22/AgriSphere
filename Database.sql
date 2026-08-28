-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 04, 2026 at 09:53 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cse311 lab project`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `permission_level` enum('Active','Suspanded','Banned') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `permission_level`) VALUES
(7601037, 'Active'),
(9500101, 'Active'),
(9999999, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `ai_log`
--

CREATE TABLE `ai_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` varchar(20) DEFAULT NULL,
  `query` varchar(300) DEFAULT NULL,
  `response` varchar(1000) DEFAULT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_log`
--

INSERT INTO `ai_log` (`log_id`, `user_id`, `user_type`, `query`, `response`, `timestamp`) VALUES
(1, 9500101, 'admin', 'Business growth Strategies', 'As your platform assistant, I can help with:\n\n- Platform analytics and performance insights\n- User engagement strategies\n- Product quality control recommendations\n- Order management optimization\n- Business growth strategies\n\nPlease ask specific questions about any of these topics!', '2025-12-19 21:23:45'),
(2, 9500101, 'admin', 'Business growth strategies', 'As your platform assistant, I can help with:\n\n- Platform analytics and performance insights\n- User engagement strategies\n- Product quality control recommendations\n- Order management optimization\n- Business growth strategies\n\nPlease ask specific questions about any of these topics!', '2025-12-19 21:23:54'),
(3, 9500101, 'admin', 'User engagement strategies', 'Tips to Increase User Engagement:\n\n1. **For Farmers:**\n   - Send price alerts when government rates change\n   - Provide seasonal crop recommendations\n   - Offer incentives for quality products\n\n2. **For Customers:**\n   - Implement loyalty/reward points\n   - Send personalized product recommendations\n   - Offer bundle deals on popular items\n\n3. **Platform-wide:**\n   - Add product reviews and ratings\n   - Create a newsletter with farming tips\n   - Host seasonal promotions', '2025-12-19 21:24:05'),
(4, 9500101, 'admin', 'what is the capital of Bangladesh', 'The capital of Bangladesh is **Dhaka**.\n\nAs AgriBot, my primary function is to assist AgriSphere platform administrators with professional insights and actionable recommendations related to platform analytics, user engagement, product quality, order and inventory management, business growth, and customer/farmer retention.\n\nIf you have any questions or require assistance concerning these areas for the AgriSphere platform, please feel free to ask!', '2025-12-20 10:30:24'),
(5, 9500101, 'admin', 'Best practices for order management', 'Efficient order management is crucial for the operational success and reputation of AgriSphere, directly impacting user satisfaction and business growth. By streamlining these processes, we can enhance reliability, reduce costs, and improve the overall experience for both farmers and buyers.\n\nHere are best practices for optimizing order management on the AgriSphere platform:\n\n### 1. Leverage Technology for Automation & Integration\n\n*   **Implement an Advanced Order Management System (OMS):** Utilize a robust OMS that integrates seamlessly with your inventory, payment gateways, shipping carriers, and CRM. This centralizes order data, reduces manual errors, and provides a unified view of the order lifecycle.\n*   **Automate Order Status Updates:** Configure automated notifications (email, SMS, in-app) for key order milestones: confirmation, payment received, packed, dispatched, in transit, delivered, and any potential delays. This keeps users informed proactively.\n*   **Integrate with Log', '2025-12-20 10:30:42'),
(6, 7029344, 'customer', 'Nutritional information and health tips', 'Assalamu Alaikum! (Hello there!) I\'m AgriBot, your friendly shopping assistant from AgriSphere. I\'d be delighted to help you with nutritional information and health tips! It\'s fantastic you\'re thinking about eating well.\n\nEating fresh fruits and vegetables from AgriSphere is one of the best things you can do for your health. Here\'s a quick rundown:\n\n### Nutritional Powerhouses:\n\n*   **Vitamins & Minerals:** They are packed with essential vitamins like Vitamin C (great for immunity, found in guava, oranges, mango), Vitamin A (good for eyes, found in carrots, spinach, pumpkin), and minerals like potassium (for heart health, found in bananas, potatoes).\n*   **Dietary Fiber:** This is super important! Fiber helps with digestion, keeps you feeling full (which can aid in weight management), and helps regulate blood sugar levels. Think leafy greens, lentils, apples, and pears.\n*   **Antioxidants:** These amazing compounds protect your body\'s cells from damage and can help reduce the risk of c', '2025-12-20 10:35:00'),
(7, 10000001, 'farmer', 'What\'s the current price of rice?', 'Hello! I can help you with the current price of rice.\n\nRice prices in Bangladesh can vary based on the type of rice and the specific market location. However, here\'s an approximate range for common rice varieties you might find:\n\n*   **Fine Rice (e.g., Miniket, Najirshail):** ৳65 - ৳75 per kg\n*   **Medium Rice (e.g., BRRI Dhan varieties):** ৳55 - ৳65 per kg\n*   **Coarse Rice (e.g., Swarna, local coarse):** ৳48 - ৳58 per kg\n\n**Please note:** These are approximate retail prices. For the most accurate and up-to-date prices in your specific area, I recommend checking with your local market vendors.', '2025-12-20 21:36:38'),
(8, 10000002, 'customer', 'Storage and cooking tips', 'Assalamu Alaikum! Hello there! I\'m AgriBot, your friendly shopping assistant from AgriSphere. I\'d be absolutely delighted to help you with some fantastic storage and cooking tips to make the most of your fresh farm produce!\n\nLet\'s dive right in:\n\n### 🥬 **Smart Storage Tips to Keep Your Produce Fresh Longer**\n\nKeeping your fruits and veggies fresh means less waste and more nutrition for your family!\n\n1.  **Leafy Greens (Spinach, Pui Shak, Lal Shak, Coriander):**\n    *   **Tip:** Wash them thoroughly, then pat them *very* dry with a clean cloth or paper towel. Wrap them loosely in a dry paper towel and store them in an airtight container or a plastic bag in your refrigerator\'s crisper drawer. This keeps them crisp for up to a week!\n2.  **Root Vegetables (Potatoes, Onions, Garlic):**\n    *   **Tip:** Store these in a cool, dark, and dry place (like a pantry or a mesh bag) away from direct sunlight. Keep potatoes and onions separate, as onions release gases that can make potatoes sprout fa', '2025-12-23 00:34:44'),
(9, 10000002, 'customer', 'Best fish in the market as of current market price in Bangladesh', 'Hello there! That\'s a fantastic question, and I\'d love to help you find some great fish options for your kitchen! 🐟\n\nWhen it comes to the \"best fish\" in the market at a current price, it can be a little subjective because \"best\" can mean different things to different people – taste, nutritional value, versatility in cooking, or simply the most bang for your buck! Also, prices can vary a bit depending on the specific market you\'re in (e.g., a local neighborhood bazaar vs. a larger city market), the time of day, and the freshness and size of the fish.\n\nHowever, I can definitely point you towards some excellent and generally good-value fish options that are popular and widely available in Bangladesh:\n\n1.  **Rohu (Rui) & Catla (Katla):** These are classic choices! They are freshwater carps, very popular for curries (like Doi Maach or Kalia) and often available at a reasonable price. They have a lovely firm texture and a mild, delicious flavor.\n    *   **Typical Price Range:** You can usual', '2025-12-23 00:35:49'),
(10, 7029193, 'farmer', 'Best time to plant tomatoes?', 'Hello! As AgriBot, I can help you with that.\n\nThe best time to plant tomatoes in Bangladesh primarily depends on the season to ensure optimal growth and yield.\n\n*   **Rabi Season (Winter):** This is the most favorable time. You should aim to plant your tomato seedlings from **October to December**. The cooler, drier weather during this period is ideal for tomato growth, flowering, and fruiting, leading to higher yields and fewer disease issues.\n*   **Kharif Season (Summer/Monsoon):** While challenging due to high temperatures and humidity, some farmers plant early varieties in **March to April**. However, you\'ll need heat-tolerant varieties and extra care to manage diseases and ensure fruit set.\n\n**Recommendation:** For the best results, focus on the **Rabi season (October-December)**.', '2025-12-23 00:44:03'),
(11, 9999999, 'admin', 'Should we approve the products that don\'t follow the government set prices?', 'As AgriBot, my recommendation is clear and data-driven: **AgriSphere should not approve products that do not follow government-set prices.**\n\nThis decision is critical for maintaining the platform\'s integrity, ensuring legal compliance, and fostering long-term trust with both farmers and customers.\n\nHere\'s a breakdown of the rationale and actionable recommendations:\n\n### Rationale for Non-Approval:\n\n1.  **Legal & Regulatory Compliance:**\n    *   **Risk:** Approving non-compliant products exposes AgriSphere to significant legal penalties, fines, operational restrictions, or even suspension of services from regulatory bodies.\n    *   **Impact:** This directly jeopardizes the platform\'s ability to operate and can lead to substantial financial losses and legal costs.\n2.  **Reputational Damage & Trust:**\n    *   **Risk:** Allowing price non-compliance erodes trust with customers who expect fair and regulated pricing. It can also damage AgriSphere\'s reputation as a reliable and ethical marke', '2025-12-23 00:51:55'),
(12, 9999999, 'admin', 'Make it short', 'Understood. I\'m ready to assist concisely with any of your AgriSphere platform needs.\n\nPlease let me know what specific area you\'d like to discuss (e.g., analytics, user engagement, order management), and I\'ll provide a brief, actionable response.', '2025-12-23 00:52:09'),
(13, 7029193, 'farmer', 'Whats the most popular vegetavle in the market right now', 'Based on current seasonality (late spring/early summer) and market demand in Bangladesh, some of the most popular and readily available vegetables right now include:\n\n*   **পটল (Potol / Pointed Gourd):** This is a very popular summer vegetable.\n    *   **Current Market Price:** Around ৳40-60 per kg.\n*   **ঢেঁড়স (Dherosh / Okra / Lady\'s Finger):** Another staple summer vegetable, high in demand.\n    *   **Current Market Price:** Around ৳30-50 per kg.\n*   **ঝিঙা (Jhinga / Ridge Gourd) and ধুন্দুল (Dhundul / Sponge Gourd):** These gourds are also very common and popular during this season.\n    *   **Current Market Price:** Around ৳35-55 per kg.\n*   **বেগুন (Begun / Eggplant / Brinjal):** Certain varieties are available year-round and remain a consistent favorite.\n    *   **Current Market Price:** Around ৳40-70 per kg, depending on variety and quality.\n\nThese vegetables are popular due to their freshness, abundance, and versatility in various Bangladeshi dishes. Prices can vary slightly ba', '2025-12-26 16:30:18'),
(14, 7250934, 'customer', 'Healthy fruits for diabetics?', 'Hello there! 👋 That\'s a fantastic and very important question. Choosing the right fruits is key for managing diabetes while still enjoying nature\'s sweet treats. As AgriBot, I\'m here to help you navigate the best options from AgriSphere\'s fresh farm selection!\n\nFor people with diabetes, the goal is to choose fruits that are rich in fiber, vitamins, and minerals, but have a lower glycemic index (GI). This means they release sugar into your bloodstream more slowly, helping to prevent spikes.\n\nHere are some healthy fruit options that are generally great for diabetics, often available fresh from our farms in Bangladesh:\n\n**Excellent Choices (Lower Glycemic Index & High Fiber):**\n\n1.  **Guava (পেয়ারা - Peyara):** This local gem is absolutely wonderful! Guava is packed with fiber and Vitamin C, which helps regulate blood sugar levels. Eating it with the skin is even better for fiber intake.\n2.  **Jamun (জাম - Jam):** A seasonal favorite, Jamun is traditionally known for its benefits in mana', '2025-12-26 16:34:09'),
(15, 7601037, 'admin', 'Tips to increase user engagement', 'To increase user engagement on the AgriSphere platform, a multi-faceted and data-driven approach is essential. Our goal is to ensure users find consistent value, feel connected, and have a seamless experience.\n\nHere are actionable recommendations, categorized for clarity:\n\n---\n\n### **Strategies to Increase User Engagement on AgriSphere**\n\n**1. Enhance Content and Value Proposition:**\n    *   **Personalized Content Feeds:** Implement algorithms to deliver highly relevant content (e.g., market prices for specific crops, localized weather forecasts, pest alerts for their region, best practices for their farm type) based on user profiles, past interactions, and stated preferences.\n        *   *Action:* Develop user segmentation based on farm size, crop type, location, and purchase history to tailor news, articles, and recommendations.\n    *   **Educational Resources & Knowledge Hub:** Provide a rich library of articles, webinars, video tutorials, and FAQs on sustainable farming, new agricu', '2025-12-26 16:39:33'),
(16, 7029193, 'farmer', 'Best time to plant tomatoes?', 'Namaste! I\'m AgriBot, ready to help you with your tomato cultivation.\n\nFor the best results in Bangladesh, the ideal time to plant tomatoes is during the **Robi season (winter)**.\n\n*   **Main Planting Season:** **October to December**\n    *   This period offers moderate temperatures (neither too hot nor too cold), which is perfect for tomato growth and fruit setting.\n    *   You\'ll get higher yields and better quality tomatoes.\n\n*   **Other Possibilities (with care):**\n    *   **Early Kharif (Summer):** If you use heat-tolerant varieties, you can plant in **February-March**. However, you\'ll need to protect them from intense heat and early monsoon rains.\n    *   **Late Kharif (Monsoon):** This is generally not recommended due to high humidity and heavy rainfall, which can lead to diseases.\n\n**Recommendation:** Stick to the **October-December** window for the most successful tomato crop!', '2025-12-26 21:01:40'),
(17, 7601037, 'admin', 'What are the vitamins in apple', 'Thank you for your question regarding the vitamins found in apples.\n\nAs AgriBot, my role is to assist AgriSphere platform administrators with business-centric topics such as platform analytics, user engagement strategies, product quality control recommendations, order management optimization, inventory management insights, business growth strategies, customer/farmer retention, and dispute resolution.\n\nQuestions about the specific nutritional content of produce, such as the vitamins in apples, fall outside my scope of expertise. My focus is on providing data-driven recommendations and insights to enhance the operational efficiency and strategic growth of the AgriSphere platform.\n\nIf you have any questions related to these areas, please feel free to ask!', '2025-12-26 22:11:03'),
(18, 7601037, 'admin', 'can we charge the customer more from the govt set price', 'As AgriBot, I must emphasize that charging customers more than a government-set price is generally **not permissible and carries significant legal and operational risks.**\n\nGovernment-set prices are typically established to ensure fair market practices, protect consumers, or support specific agricultural sectors. Deviating from these regulated prices can lead to severe consequences for AgriSphere and its users.\n\n**Key Considerations and Risks:**\n\n1.  **Legal & Regulatory Compliance:** Violating government price controls can result in substantial fines, legal action, license revocation, and mandatory refunds.\n2.  **Reputational Damage:** Such practices can severely erode customer trust, damage AgriSphere\'s brand reputation, and lead to negative publicity.\n3.  **Loss of Business:** Customers may choose alternative platforms or suppliers that adhere to official pricing, leading to decreased user engagement and transaction volume.\n4.  **Platform Integrity:** Allowing or facilitating such p', '2025-12-26 22:12:25'),
(19, 7601037, 'admin', 'Best practices for order management', 'As AgriBot, I understand that efficient order management is crucial for the AgriSphere platform\'s operational excellence, user satisfaction, and overall business growth. Streamlined order processes directly impact farmer and buyer retention, reduce operational costs, and enhance the platform\'s reliability.\n\nHere are best practices for order management, focusing on data-driven and actionable recommendations:\n\n### Best Practices for Order Management\n\n1.  **Automate Core Order Processes:**\n    *   **Recommendation:** Implement robust automation for order receipt, confirmation, and status updates. This includes automated notifications to both buyers and sellers at each stage (order placed, payment confirmed, dispatched, delivered).\n    *   **Data Focus:** Track **\"Order Processing Time\"** and **\"Time to First Notification.\"** Automation should significantly reduce these metrics, leading to faster fulfillment cycles.\n    *   **Actionable Step:** Leverage platform features to auto-generate i', '2025-12-26 22:13:03'),
(20, 7283944, 'farmer', 'Best time to plant tomatoes?', 'Hello! Here\'s the best time to plant tomatoes in Bangladesh:\n\n*   **Main Season (Rabi/Winter):** This is the ideal time.\n    *   **Planting:** Mid-October to Mid-November.\n    *   **Why:** Cooler, drier weather is perfect for tomato growth, leading to better yields and fewer pest/disease issues.\n*   **Off-Season (Kharif/Summer):** Possible with special care.\n    *   **Planting:** March to April.\n    *   **Why:** Requires heat-tolerant varieties and good drainage to manage the monsoon rains and humidity. Yields might be lower, and more protection from pests/diseases is needed.\n\nFor best results, aim for the main Rabi season!', '2025-12-27 16:02:30'),
(21, 7283944, 'farmer', 'Best fertilizers to use in winter?', 'Hello! For winter crops, focusing on nutrients that promote strong root growth, cold tolerance, and good flowering/fruiting is key.\n\nHere are some best fertilizers for winter use in Bangladesh:\n\n*   **DAP (Di-ammonium Phosphate):**\n    *   **Why in Winter?** Provides both Nitrogen (N) for early growth and a high amount of Phosphorus (P) which is crucial for strong root development, cold tolerance, and flowering in winter crops.\n    *   **Approximate Cost:** ৳45-50 per kg.\n*   **MOP (Muriate of Potash):**\n    *   **Why in Winter?** Potassium (K) helps plants resist cold stress, improves water uptake, enhances disease resistance, and improves the quality of fruits and vegetables.\n    *   **Approximate Cost:** ৳30-35 per kg.\n*   **Urea:**\n    *   **Why in Winter?** Provides essential Nitrogen for leafy growth. Use it if your soil needs more N, especially for leafy greens. Be careful not to over-apply, as too much N can sometimes make plants more susceptible to cold.\n    *   **Approximate ', '2025-12-27 16:03:07'),
(22, 10000004, 'farmer', 'What\'s the current price of rice?', 'Hello! As AgriBot, I can give you an estimated current price range for rice in Bangladesh. Please note that prices can vary based on the specific type of rice (e.g., coarse, medium, fine), quality, location, and market demand.\n\nHere\'s an estimated range for common types of rice in local markets:\n\n*   **Coarse Rice (Mota Chaal):** ৳48 - ৳55 per kg\n*   **Medium Rice (Majhari Chaal):** ৳55 - ৳65 per kg\n*   **Fine Rice (Jhinuk Chaal/Miniket/Nazirshail):** ৳65 - ৳80+ per kg\n\nFor the most accurate current prices in your specific area, it\'s always best to check with local markets or reliable online agricultural market platforms.', '2025-12-27 17:29:20'),
(23, 10000004, 'farmer', 'How is the weather in Dhaka today', 'Hello there!\n\nAs AgriBot, I don\'t have real-time access to live weather data for today in Dhaka. My information is based on pre-trained data.\n\nHowever, knowing the weather is crucial for your farming decisions!\n\n**Here\'s how you can get the most accurate, up-to-the-minute weather for Dhaka:**\n\n*   **Check a reliable weather app:** Apps like AccuWeather, Google Weather, or local news apps often provide detailed forecasts.\n*   **Visit the Bangladesh Meteorological Department (BMD) website:** They provide official forecasts for the country.\n*   **Listen to local news channels or radio:** They frequently update weather reports.\n\n**Once you have the weather information (e.g., temperature, rainfall, humidity), I can help you with advice like:**\n\n*   When to irrigate based on rainfall.\n*   Best times for spraying fertilizers or pesticides.\n*   Protecting crops from extreme heat or sudden heavy rains.\n\nLet me know what the forecast says, and I\'ll provide specific farming advice!', '2025-12-27 17:29:49'),
(24, 10000004, 'farmer', 'What\'s the current price of rice?', 'Hello! I can help you with the current market prices for rice.\n\nThe price of rice can vary a bit depending on the type (variety), quality, and your specific location in Bangladesh.\n\nHere\'s an approximate range for common rice varieties in the market right now:\n\n*   **Coarse Rice (e.g., Swarna, BRRI Dhan 28/29):** ৳48 - ৳55 per kg\n*   **Medium Rice (e.g., BRRI Dhan 50/58, Paijam):** ৳55 - ৳65 per kg\n*   **Fine Rice (e.g., Miniket, Nazirshail):** ৳65 - ৳75 per kg\n\n**Please note:** These are approximate prices. For the most accurate daily prices in your specific area, it\'s best to check your local market or with local traders.', '2025-12-27 17:30:25'),
(25, 10000004, 'farmer', 'Best time to plant tomatoes?', 'Hello! As AgriBot, I can tell you the best time to plant tomatoes in Bangladesh for optimal yield is during the **winter season**.\n\n*   **Main Planting Season:** Late September to early November.\n    *   This allows the plants to establish before the peak cold and fruit during the cooler, dry winter months (December to February), which tomatoes prefer.\n*   **Off-Season (with care):** You can also plant heat-tolerant varieties in early spring (February-March) for a summer harvest, but yields might be lower due to heat and humidity.\n\nFor the best results, aim for planting your tomato seedlings between late September and early November.', '2025-12-27 17:41:02'),
(26, 10000004, 'farmer', 'What\'s the current price of rice?', 'Hello! The price of rice can vary depending on the type, quality, and your location in Bangladesh.\n\nHere are the approximate retail prices per kilogram (kg) for common types of rice currently:\n\n*   **Coarse Rice (মোটা চাল):** ৳50 - ৳55 per kg\n*   **Medium Rice (মাঝারি চাল like Paijam, Latashail):** ৳55 - ৳65 per kg\n*   **Fine Rice (সরু চাল like Miniket, Najirshail):** ৳65 - ৳80+ per kg\n\nPlease let me know if you are interested in a specific type of rice or a particular region, and I can try to provide a more precise estimate!', '2025-12-27 17:41:44');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `category_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `category_name`, `description`, `category_image`) VALUES
(1, 'Vegetables', 'Fresh vegetables straight from local farms. Healthy, chemical-free, and perfect for everyday cooking.', NULL),
(2, 'Fruits', 'Seasonal and fresh fruits directly from farms. Naturally sweet, healthy, and perfect for daily consumption.', NULL),
(3, 'Grains', 'High-quality rice, wheat, and other grains. Clean, nutritious, and essential for everyday meals.', NULL),
(4, 'Spices', 'Pure and aromatic spices used in daily cooking. Adds flavor, aroma, and authenticity to every dish.', NULL),
(5, 'Dairy', 'Fresh dairy products like milk, yogurt, ghee, butter, and paneer. Essential for daily nutrition and traditional cooking.', NULL),
(6, 'Fish', 'Freshwater and sea fish sourced from trusted farms and rivers. Rich in protein and a staple of Bangladeshi meals.', NULL),
(8, 'Poultry', 'Farm-raised chicken, duck, and eggs. Fresh, protein-rich, and ideal for healthy cooking.', NULL),
(18, 'Meat', 'Fresh and hygienic meat including beef, mutton, and more. Carefully sourced for quality and taste.', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` int(11) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `address`, `phone`) VALUES
(7029344, 'House-180, Road-38, Block-C, Union/Ward-5, Barishal Sadar, Barishal-8200, Building-41, Lat-23.742941, Lon-90.313654', '01722334455'),
(7065411, 'House-150, Road-42, Block-B, Union/Ward-18, Barishal Sadar, Barishal-8200, Building-39, Lat-23.635330, Lon-90.553752', '01555667722'),
(7123988, 'House-138, Road-11, Block-E, Union/Ward-8, Khulna Sadar, Khulna-9000, Building-47, Lat-24.059179, Lon-90.236704', '01766778812'),
(7159033, 'House-116, Road-19, Block-E, Union/Ward-5, Rajshahi Sadar, Rajshahi-6000, Building-7, Lat-23.522829, Lon-90.623877', '01555667788'),
(7165321, 'House-129, Road-59, Block-F, Union/Ward-16, Mirpur-10, Dhaka-1216, Building-21, Lat-23.877794, Lon-90.440299', '01911223366'),
(7183440, 'House-163, Road-36, Block-D, Union/Ward-1, Pahartali, Chattogram-4212, Building-46, Lat-23.760083, Lon-90.191111', '01988990033'),
(7203401, 'House-108, Road-18, Block-F, Union/Ward-7, Mirpur-10, Dhaka-1216, Building-46, Lat-23.510467, Lon-90.327256', '01799001122'),
(7219033, 'House-116, Road-56, Block-F, Union/Ward-11, Dhanmondi, Dhaka-1209, Building-32, Lat-23.541929, Lon-90.374417', '01800112266'),
(7250934, 'House-157, Road-44, Block-B, Union/Ward-6, Ambarkhana, Sylhet-3100, Building-27, Lat-23.684286, Lon-90.603039', '01988990044'),
(7283590, 'House-161, Road-52, Block-F, Union/Ward-18, Bogura Sadar, Bogura-5800, Building-6, Lat-23.721773, Lon-90.263323', '01766778899'),
(7331220, 'House-44, Road-23, Block-B, Union/Ward-3, Barishal Sadar, Barishal-8200, Building-6, Lat-24.099638, Lon-90.760889', '01944556688'),
(7338912, 'House-31, Road-56, Block-A, Union/Ward-1, Rajshahi Sadar, Rajshahi-6000, Building-45, Lat-23.984952, Lon-90.658644', '01833445599'),
(7412891, 'House-183, Road-10, Block-A, Union/Ward-20, Rajshahi Sadar, Rajshahi-6000, Building-41, Lat-23.785047, Lon-90.535506', '01911223377'),
(7419083, 'House-178, Road-35, Block-B, Union/Ward-9, Pahartali, Chattogram-4212, Building-25, Lat-23.803987, Lon-90.587030', '01800112277'),
(7429834, 'House-36, Road-48, Block-C, Union/Ward-16, Noakhali Sadar, Noakhali-3800, Building-2, Lat-23.582789, Lon-90.439805', '01833445588'),
(7440193, 'House-15, Road-55, Block-B, Union/Ward-17, Uttara Sector-4, Dhaka-1230, Building-37, Lat-24.159114, Lon-90.445989', '01766778891'),
(7450001, 'House-168, Road-31, Block-A, Union/Ward-11, Pahartali, Chattogram-4212', '01810000001'),
(7450002, 'House-52, Road-37, Block-B, Union/Ward-10, Pahartali, Chattogram-4212', '01810000002'),
(7450003, 'House-69, Road-5, Block-C, Union/Ward-10, Uttara Sector-4, Dhaka-1230', '01810000003'),
(7450004, 'House-67, Road-37, Block-A, Union/Ward-10, Mirpur-10, Dhaka-1216', '01810000004'),
(7450005, 'House-145, Road-54, Block-B, Union/Ward-18, Kotwali, Chattogram-4000', '01810000005'),
(7450006, 'House-146, Road-14, Block-F, Union/Ward-18, Ambarkhana, Sylhet-3100', '01810000006'),
(7450007, 'House-134, Road-22, Block-E, Union/Ward-16, Pahartali, Chattogram-4212', '01810000007'),
(7450008, 'House-115, Road-8, Block-F, Union/Ward-5, Kotwali, Chattogram-4000', '01810000008'),
(7450009, 'House-93, Road-60, Block-D, Union/Ward-18, Ambarkhana, Sylhet-3100', '01810000009'),
(7450010, 'House-146, Road-38, Block-F, Union/Ward-15, Rajshahi Sadar, Rajshahi-6000', '01810000010'),
(7450011, 'House-173, Road-56, Block-A, Union/Ward-11, Uttara Sector-4, Dhaka-1230', '01810000011'),
(7450012, 'House-56, Road-19, Block-E, Union/Ward-11, Ambarkhana, Sylhet-3100', '01810000012'),
(7450013, 'House-100, Road-38, Block-D, Union/Ward-7, Rajshahi Sadar, Rajshahi-6000', '01810000013'),
(7450014, 'House-171, Road-56, Block-A, Union/Ward-13, Khulna Sadar, Khulna-9000', '01810000014'),
(7450015, 'House-90, Road-39, Block-F, Union/Ward-11, Barishal Sadar, Barishal-8200', '01810000015'),
(7450016, 'House-57, Road-30, Block-D, Union/Ward-13, Mirpur-10, Dhaka-1216', '01810000016'),
(7450017, 'House-70, Road-1, Block-F, Union/Ward-18, Kotwali, Chattogram-4000', '01810000017'),
(7450018, 'House-136, Road-1, Block-F, Union/Ward-17, Mirpur-10, Dhaka-1216', '01810000018'),
(7450019, 'House-171, Road-28, Block-E, Union/Ward-5, Barishal Sadar, Barishal-8200', '01810000019'),
(7450020, 'House-22, Road-40, Block-A, Union/Ward-2, Uttara Sector-4, Dhaka-1230', '01810000020'),
(7450021, 'House-98, Road-24, Block-D, Union/Ward-9, Kotwali, Chattogram-4000', '01810000021'),
(7450022, 'House-21, Road-8, Block-C, Union/Ward-7, Pahartali, Chattogram-4212', '01810000022'),
(7450023, 'House-195, Road-6, Block-D, Union/Ward-7, Gulshan-2, Dhaka-1212', '01810000023'),
(7450024, 'House-148, Road-15, Block-F, Union/Ward-2, Kotwali, Chattogram-4000', '01810000024'),
(7450025, 'House-26, Road-15, Block-F, Union/Ward-10, Khulna Sadar, Khulna-9000', '01810000025'),
(7450026, 'House-189, Road-6, Block-D, Union/Ward-15, Khulna Sadar, Khulna-9000', '01810000026'),
(7450027, 'House-101, Road-52, Block-E, Union/Ward-8, Pahartali, Chattogram-4212', '01810000027'),
(7450028, 'House-108, Road-4, Block-E, Union/Ward-8, Khulna Sadar, Khulna-9000', '01810000028'),
(7450029, 'House-171, Road-51, Block-E, Union/Ward-17, Gulshan-2, Dhaka-1212', '01810000029'),
(7450030, 'House-41, Road-36, Block-C, Union/Ward-20, Barishal Sadar, Barishal-8200', '01810000030'),
(7450031, 'House-109, Road-60, Block-B, Union/Ward-14, Uttara Sector-4, Dhaka-1230', '01810000031'),
(7450032, 'House-168, Road-5, Block-F, Union/Ward-2, Khulna Sadar, Khulna-9000', '01810000032'),
(7450033, 'House-30, Road-5, Block-F, Union/Ward-11, Rajshahi Sadar, Rajshahi-6000', '01810000033'),
(7450034, 'House-13, Road-9, Block-D, Union/Ward-7, Barishal Sadar, Barishal-8200', '01810000034'),
(7450035, 'House-184, Road-40, Block-D, Union/Ward-12, Kotwali, Chattogram-4000', '01810000035'),
(7450036, 'House-98, Road-5, Block-F, Union/Ward-4, Mirpur-10, Dhaka-1216', '01810000036'),
(7450037, 'House-172, Road-27, Block-D, Union/Ward-15, Khulna Sadar, Khulna-9000', '01810000037'),
(7450038, 'House-15, Road-49, Block-E, Union/Ward-11, Gulshan-2, Dhaka-1212', '01810000038'),
(7450039, 'House-64, Road-7, Block-D, Union/Ward-9, Pahartali, Chattogram-4212', '01810000039'),
(7450040, 'House-45, Road-38, Block-C, Union/Ward-5, Barishal Sadar, Barishal-8200', '01810000040'),
(7450041, 'House-19, Road-34, Block-D, Union/Ward-18, Khulna Sadar, Khulna-9000', '01810000041'),
(7450042, 'House-142, Road-57, Block-D, Union/Ward-20, Gulshan-2, Dhaka-1212', '01810000042'),
(7450043, 'House-156, Road-30, Block-A, Union/Ward-4, Kotwali, Chattogram-4000', '01810000043'),
(7450044, 'House-193, Road-19, Block-D, Union/Ward-8, Barishal Sadar, Barishal-8200', '01810000044'),
(7450045, 'House-135, Road-28, Block-B, Union/Ward-2, Kotwali, Chattogram-4000', '01810000045'),
(7450046, 'House-174, Road-8, Block-A, Union/Ward-14, Uttara Sector-4, Dhaka-1230', '01810000046'),
(7450047, 'House-154, Road-46, Block-D, Union/Ward-6, Khulna Sadar, Khulna-9000', '01810000047'),
(7450048, 'House-94, Road-45, Block-C, Union/Ward-9, Gulshan-2, Dhaka-1212', '01810000048'),
(7450049, 'House-114, Road-19, Block-F, Union/Ward-9, Kotwali, Chattogram-4000', '01810000049'),
(7450050, 'House-192, Road-29, Block-D, Union/Ward-4, Uttara Sector-4, Dhaka-1230', '01810000050'),
(7450051, 'House-40, Road-57, Block-A, Union/Ward-15, Uttara Sector-4, Dhaka-1230', '01810000051'),
(7450052, 'House-127, Road-3, Block-B, Union/Ward-10, Kotwali, Chattogram-4000', '01810000052'),
(7450053, 'House-158, Road-36, Block-D, Union/Ward-4, Gulshan-2, Dhaka-1212', '01810000053'),
(7450054, 'House-21, Road-8, Block-B, Union/Ward-4, Barishal Sadar, Barishal-8200', '01810000054'),
(7450055, 'House-78, Road-59, Block-E, Union/Ward-15, Pahartali, Chattogram-4212', '01810000055'),
(7450056, 'House-62, Road-2, Block-A, Union/Ward-16, Uttara Sector-4, Dhaka-1230', '01810000056'),
(7450057, 'House-100, Road-25, Block-D, Union/Ward-10, Ambarkhana, Sylhet-3100', '01810000057'),
(7450058, 'House-2, Road-1, Block-A, Union/Ward-1, Dhanmondi, Dhaka-1209', '01810000058'),
(7450059, 'House-63, Road-21, Block-E, Union/Ward-17, Khulna Sadar, Khulna-9000', '01810000059'),
(7450060, 'House-130, Road-46, Block-F, Union/Ward-20, Mirpur-10, Dhaka-1216', '01810000060'),
(7450061, 'House-75, Road-8, Block-D, Union/Ward-3, Gulshan-2, Dhaka-1212', '01810000061'),
(7450062, 'House-95, Road-51, Block-E, Union/Ward-7, Kotwali, Chattogram-4000', '01810000062'),
(7450063, 'House-1, Road-49, Block-F, Union/Ward-11, Rajshahi Sadar, Rajshahi-6000', '01810000063'),
(7450064, 'House-29, Road-27, Block-E, Union/Ward-12, Pahartali, Chattogram-4212', '01810000064'),
(7450065, 'House-44, Road-19, Block-F, Union/Ward-9, Kotwali, Chattogram-4000', '01810000065'),
(7450066, 'House-177, Road-10, Block-A, Union/Ward-7, Gulshan-2, Dhaka-1212', '01810000066'),
(7569211, 'House-130, Road-46, Block-E, Union/Ward-17, Rajshahi Sadar, Rajshahi-6000, Building-26, Lat-24.050155, Lon-90.385780', '01944556699'),
(7600001, 'House-132, Road-22, Block-F, Union/Ward-3, Barishal Sadar, Barishal-8200', '01820000001'),
(7600002, 'House-135, Road-23, Block-F, Union/Ward-5, Ambarkhana, Sylhet-3100', '01820000002'),
(7600003, 'House-54, Road-32, Block-E, Union/Ward-11, Mirpur-10, Dhaka-1216', '01820000003'),
(7600004, 'House-99, Road-48, Block-D, Union/Ward-3, Gulshan-2, Dhaka-1212', '01820000004'),
(7600005, 'House-36, Road-35, Block-B, Union/Ward-16, Dhanmondi, Dhaka-1209', '01820000005'),
(7600006, 'House-197, Road-42, Block-D, Union/Ward-12, Uttara Sector-4, Dhaka-1230', '01820000006'),
(7600007, 'House-164, Road-9, Block-B, Union/Ward-18, Pahartali, Chattogram-4212', '01820000007'),
(7600008, 'House-198, Road-25, Block-A, Union/Ward-1, Dhanmondi, Dhaka-1209', '01820000008'),
(7600009, 'House-23, Road-27, Block-E, Union/Ward-17, Ambarkhana, Sylhet-3100', '01820000009'),
(7600010, 'House-139, Road-35, Block-E, Union/Ward-6, Khulna Sadar, Khulna-9000', '01820000010'),
(7600011, 'House-122, Road-25, Block-B, Union/Ward-2, Pahartali, Chattogram-4212', '01820000011'),
(7600012, 'House-84, Road-32, Block-C, Union/Ward-4, Rajshahi Sadar, Rajshahi-6000', '01820000012'),
(7600013, 'House-53, Road-5, Block-D, Union/Ward-15, Khulna Sadar, Khulna-9000', '01820000013'),
(7600014, 'House-198, Road-28, Block-B, Union/Ward-5, Gulshan-2, Dhaka-1212', '01820000014'),
(7600015, 'House-197, Road-33, Block-E, Union/Ward-4, Pahartali, Chattogram-4212', '01820000015'),
(7600016, 'House-35, Road-17, Block-F, Union/Ward-11, Dhanmondi, Dhaka-1209', '01820000016'),
(7600017, 'House-122, Road-56, Block-E, Union/Ward-2, Dhanmondi, Dhaka-1209', '01820000017'),
(7600018, 'House-171, Road-16, Block-E, Union/Ward-15, Kotwali, Chattogram-4000', '01820000018'),
(7600019, 'House-48, Road-46, Block-A, Union/Ward-4, Ambarkhana, Sylhet-3100', '01820000019'),
(7600020, 'House-108, Road-52, Block-D, Union/Ward-15, Pahartali, Chattogram-4212', '01820000020'),
(7600021, 'House-172, Road-30, Block-F, Union/Ward-1, Kotwali, Chattogram-4000', '01820000021'),
(7600022, 'House-55, Road-56, Block-E, Union/Ward-7, Mirpur-10, Dhaka-1216', '01820000022'),
(7600023, 'House-6, Road-32, Block-D, Union/Ward-3, Barishal Sadar, Barishal-8200', '01820000023'),
(7600024, 'House-52, Road-34, Block-F, Union/Ward-6, Kotwali, Chattogram-4000', '01820000024'),
(7600025, 'House-76, Road-34, Block-D, Union/Ward-10, Kotwali, Chattogram-4000', '01820000025'),
(7600026, 'House-141, Road-17, Block-B, Union/Ward-13, Mirpur-10, Dhaka-1216', '01820000026'),
(7600027, 'House-36, Road-17, Block-F, Union/Ward-9, Pahartali, Chattogram-4212', '01820000027'),
(7600028, 'House-80, Road-25, Block-F, Union/Ward-20, Uttara Sector-4, Dhaka-1230', '01820000028'),
(7600029, 'House-3, Road-54, Block-C, Union/Ward-7, Uttara Sector-4, Dhaka-1230', '01820000029'),
(7600030, 'House-162, Road-60, Block-D, Union/Ward-18, Pahartali, Chattogram-4212', '01820000030'),
(7600031, 'House-45, Road-28, Block-D, Union/Ward-13, Uttara Sector-4, Dhaka-1230', '01820000031'),
(7600032, 'House-195, Road-45, Block-E, Union/Ward-12, Ambarkhana, Sylhet-3100', '01820000032'),
(7600033, 'House-113, Road-49, Block-C, Union/Ward-9, Dhanmondi, Dhaka-1209', '01820000033'),
(7600034, 'House-15, Road-8, Block-C, Union/Ward-12, Rajshahi Sadar, Rajshahi-6000', '01820000034'),
(7600035, 'House-3, Road-49, Block-A, Union/Ward-12, Barishal Sadar, Barishal-8200', '01820000035'),
(7600036, 'House-178, Road-39, Block-D, Union/Ward-18, Rajshahi Sadar, Rajshahi-6000', '01820000036'),
(7600037, 'House-183, Road-26, Block-C, Union/Ward-15, Kotwali, Chattogram-4000', '01820000037'),
(7600038, 'House-173, Road-7, Block-F, Union/Ward-8, Dhanmondi, Dhaka-1209', '01820000038'),
(7600039, 'House-49, Road-59, Block-B, Union/Ward-19, Dhanmondi, Dhaka-1209', '01820000039'),
(7600040, 'House-132, Road-3, Block-B, Union/Ward-19, Barishal Sadar, Barishal-8200', '01820000040'),
(7634891, 'House-137, Road-12, Block-F, Union/Ward-20, Mirpur-10, Dhaka-1216, Building-50, Lat-23.786889, Lon-90.141947', '01555667789'),
(7644433, 'House-15, Road-11, Block-D, Union/Ward-15, Rajshahi Sadar, Rajshahi-6000, Building-43, Lat-23.816618, Lon-90.607600', '01722334466'),
(7648201, 'House-54, Road-11, Block-A, Union/Ward-14, Uttara Sector-4, Dhaka-1230, Building-16, Lat-24.011165, Lon-90.613916', '01911223344'),
(7701893, 'House-97, Road-12, Block-D, Union/Ward-4, Gulshan-2, Dhaka-1212, Building-10, Lat-23.895297, Lon-90.272431', '01799001166'),
(7732091, 'House-108, Road-57, Block-A, Union/Ward-17, Khulna Sadar, Khulna-9000, Building-40, Lat-23.522042, Lon-90.647515', '01722334477'),
(7812393, 'House-164, Road-45, Block-B, Union/Ward-19, Comilla Sadar, Comilla-3500, Building-4, Lat-23.806387, Lon-90.782318', '01877889944'),
(7829891, 'House-113, Road-53, Block-E, Union/Ward-19, Zindabazar, Sylhet-3100, Building-34, Lat-24.124729, Lon-90.411814', '01555667790'),
(7845990, 'House-110, Road-25, Block-C, Union/Ward-17, Barishal Sadar, Barishal-8200, Building-7, Lat-24.135016, Lon-90.184372', '01766778890'),
(7882345, 'House-177, Road-3, Block-D, Union/Ward-16, Dhanmondi, Dhaka-1209, Building-48, Lat-23.939228, Lon-90.297852', '01800112244'),
(7932441, 'House-107, Road-49, Block-C, Union/Ward-16, Rajshahi Sadar, Rajshahi-6000, Building-23, Lat-23.882900, Lon-90.388933', '01933445566'),
(7940012, 'House-85, Road-53, Block-A, Union/Ward-19, Kotwali, Chattogram-4000, Building-35, Lat-23.871362, Lon-90.527709', '01766778823'),
(7993544, 'House-93, Road-30, Block-A, Union/Ward-15, Ambarkhana, Sylhet-3100, Building-4, Lat-23.514121, Lon-90.731140', '01833445566'),
(8124593, 'House-90, Road-32, Block-B, Union/Ward-1, Gulshan-2, Dhaka-1212, Building-27, Lat-23.702229, Lon-90.703952', '01822334455'),
(9500102, 'House-69, Road-55, Block-D, Union/Ward-18, Rajshahi Sadar, Rajshahi-6000', '011231231'),
(9500105, 'House-186, Road-53, Block-D, Union/Ward-7, Khulna Sadar, Khulna-9000', '011231231'),
(10000000, 'Dhaka, Bangladesh', '01761289072'),
(10000002, 'Bashundhara, Dhaka', '01724252612');

-- --------------------------------------------------------

--
-- Table structure for table `customer_cart`
--

CREATE TABLE `customer_cart` (
  `cart_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_cart`
--

INSERT INTO `customer_cart` (`cart_id`, `customer_id`, `product_id`, `quantity`) VALUES
(96, 7600014, 42, 6),
(97, 7600014, 36, 3),
(98, 7600014, 43, 3);

-- --------------------------------------------------------

--
-- Table structure for table `customer_order`
--

CREATE TABLE `customer_order` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `order_date` datetime NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_order`
--

INSERT INTO `customer_order` (`order_id`, `customer_id`, `total_amount`, `order_date`, `status`, `processed_at`, `shipped_at`, `delivered_at`, `cancelled_at`, `updated_by`) VALUES
(2, 7029344, 0.00, '2025-12-16 19:23:31', 'Delivered', NULL, NULL, NULL, NULL, NULL),
(3, 7732091, 20.00, '2025-12-16 19:34:37', 'Delivered', NULL, NULL, NULL, NULL, NULL),
(4, 7029344, 492.00, '2025-12-17 14:08:47', 'Cancelled', NULL, NULL, NULL, '2025-12-26 15:35:00', 7601037),
(5, 7029344, 72.00, '2025-12-17 14:34:54', 'Delivered', NULL, NULL, '2025-12-20 21:41:45', NULL, 9999999),
(6, 7029344, 45.00, '2025-12-20 01:32:03', 'Delivered', NULL, NULL, NULL, NULL, NULL),
(7, 10000002, 2400.00, '2025-12-23 00:33:52', 'Delivered', NULL, NULL, '2025-12-23 00:49:55', NULL, 9999999),
(8, 7283590, 7665.00, '2025-12-26 15:23:49', 'Delivered', NULL, NULL, '2025-12-26 15:25:19', NULL, 9999999),
(9, 7331220, 11300.00, '2025-12-26 15:32:41', 'Delivered', NULL, NULL, '2025-12-26 15:34:36', NULL, 7601037),
(10, 7250934, 9656.00, '2025-12-26 16:33:17', 'Delivered', NULL, '2025-12-26 16:37:43', '2025-12-26 16:37:53', NULL, 7601037),
(11, 7331220, 5444.00, '2025-12-26 21:04:03', 'Delivered', NULL, '2025-12-26 21:05:04', '2025-12-26 22:10:37', NULL, 7601037),
(12, 7283590, 8960.00, '2025-12-26 22:03:36', 'Delivered', NULL, NULL, '2025-12-26 22:08:34', NULL, 7601037),
(13, 7029344, 20620.00, '2025-12-26 22:54:05', 'Delivered', NULL, '2025-12-26 23:13:41', '2025-12-26 23:13:53', NULL, 7601037),
(14, 7250934, 15442.00, '2025-12-26 23:10:20', 'Delivered', NULL, NULL, '2025-12-26 23:13:48', NULL, 7601037),
(15, 7250934, 8397.00, '2025-12-26 23:11:44', 'Delivered', NULL, '2025-12-26 23:13:32', '2025-12-26 23:13:59', NULL, 7601037),
(16, 7412891, 63660.00, '2025-12-26 23:19:02', 'Delivered', NULL, NULL, '2025-12-26 23:24:21', NULL, 7601037),
(17, 7600004, 59620.00, '2025-12-26 23:21:54', 'Delivered', NULL, NULL, '2025-12-26 23:24:11', NULL, 7601037),
(18, 7600018, 90990.00, '2025-12-26 23:26:28', 'Delivered', NULL, NULL, '2025-12-26 23:28:19', NULL, 9999999),
(19, 7440193, 1939.00, '2025-12-27 00:51:04', 'Delivered', NULL, '2025-12-27 14:44:33', '2025-12-27 16:05:29', NULL, 9999999),
(20, 7450023, 7536.00, '2025-12-27 15:51:46', 'Delivered', NULL, NULL, '2025-12-27 16:05:20', NULL, 9999999),
(21, 7338912, 2416.00, '2025-12-27 15:58:07', 'Delivered', NULL, '2025-12-27 16:00:39', '2025-12-27 16:01:01', NULL, 7601037);

-- --------------------------------------------------------

--
-- Table structure for table `farmer`
--

CREATE TABLE `farmer` (
  `farmer_id` int(11) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farmer`
--

INSERT INTO `farmer` (`farmer_id`, `address`, `bank_name`, `bank_account_number`) VALUES
(7029193, 'House-2, Road-30, Village Kaliganj, Union Fulbari, Upazila Matlab, District Chandpur, Bangladesh', 'Janata Bank Limited', 'AC-0115118669'),
(7099123, 'House-50, Road-49, Village Bheramara, Union Sadar, Upazila Bagha, District Chattogram, Bangladesh', 'Janata Bank Limited', 'AC-8459413674'),
(7283944, 'House-140, Road-57, Village Fenchuganj, Union Belkuchi, Upazila Belkuchi, District Chattogram, Bangladesh', 'BRAC Bank Limited', 'AC-9997575804'),
(7300444, 'House-11, Road-16, Village Belkuchi, Union Matlab, Upazila Kaliganj, District Gazipur, Bangladesh', 'Agrani Bank Limited', 'AC-3009528893'),
(7348129, 'House-107, Road-45, Village Belkuchi, Union Bagha, Upazila Belkuchi, District Gazipur, Bangladesh', 'Rupali Bank Limited', 'AC-6460295735'),
(7431984, 'House-186, Road-42, Village Fulbari, Union Bheramara, Upazila Bagha, District Sylhet, Bangladesh', 'BRAC Bank Limited', 'AC-5280083702'),
(7452199, 'House-173, Road-44, Village Charpara, Union Fulbari, Upazila Bheramara, District Gazipur, Bangladesh', 'Islami Bank Bangladesh Limited', 'AC-5227692784'),
(7500001, 'House-24, Road-3, Village Mirzapur, Union Belkuchi, Upazila Bagha, District Cumilla, Bangladesh', 'Agrani Bank Limited', 'AC-3491552503'),
(7500002, 'House-101, Road-29, Village Matlab, Union Matlab, Upazila Matlab, District Dinajpur, Bangladesh', 'Agrani Bank Limited', 'AC-4529889761'),
(7500004, 'House-31, Road-14, Village Fulbari, Union Fenchuganj, Upazila Bheramara, District Kushtia, Bangladesh', 'BRAC Bank Limited', 'AC-8317144418'),
(7500006, 'House-78, Road-47, Village Fulbari, Union Belkuchi, Upazila Fulbari, District Kushtia, Bangladesh', 'Rupali Bank Limited', 'AC-7075413239'),
(7500007, 'House-7, Road-3, Village Belkuchi, Union Bagha, Upazila Bheramara, District Chandpur, Bangladesh', 'Dutch-Bangla Bank Limited', 'AC-9577354844'),
(7500008, 'House-105, Road-44, Village Charpara, Union Bheramara, Upazila Sadar, District Sylhet, Bangladesh', 'Agrani Bank Limited', 'AC-2936416792'),
(7500009, 'House-117, Road-3, Village Kaliganj, Union Sadar, Upazila Sadar, District Gazipur, Bangladesh', 'Islami Bank Bangladesh Limited', 'AC-3808673092'),
(7500010, 'House-37, Road-47, Village Bheramara, Union Belkuchi, Upazila Belkuchi, District Cumilla, Bangladesh', 'Dutch-Bangla Bank Limited', 'AC-3779182456'),
(7500011, 'House-71, Road-38, Village Charpara, Union Bagha, Upazila Fulbari, District Gazipur, Bangladesh', 'Agrani Bank Limited', 'AC-6139163538'),
(7500015, 'House-113, Road-33, Village Charpara, Union Bagha, Upazila Kaliganj, District Sylhet, Bangladesh', 'Rupali Bank Limited', 'AC-0471121538'),
(7502341, 'House-176, Road-49, Village Kaliganj, Union Fenchuganj, Upazila Matlab, District Sylhet, Bangladesh', 'BRAC Bank Limited', 'AC-3347037540'),
(7532190, 'House-189, Road-44, Village Matlab, Union Fenchuganj, Upazila Matlab, District Kushtia, Bangladesh', 'Janata Bank Limited', 'AC-4541213823'),
(10000003, 'House-207, Road-21, Village Belkuchi, Upazila Belkuchi, District Chattogram, Bangladesh', 'Janata Bank Limited', 'AC-0840930911'),
(10000004, 'Gazipur Cantonment', 'Rupali Bank Limited', 'AC-6101101949'),
(10000005, 'Kallyanpur, garden view Road, Dhaka', 'Rupali Bank Limited', 'AC-3264386377');

-- --------------------------------------------------------

--
-- Table structure for table `farm_inventory`
--

CREATE TABLE `farm_inventory` (
  `inventory_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity_available` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farm_inventory`
--

INSERT INTO `farm_inventory` (`inventory_id`, `product_id`, `quantity_available`) VALUES
(1, 1, 176),
(2, 2, 59),
(3, 3, 40),
(4, 4, 422),
(8, 5, 2),
(9, 6, 3),
(10, 7, 200),
(11, 8, 134),
(12, 9, 10),
(13, 10, 14),
(14, 11, 14),
(15, 12, 0),
(16, 13, 36),
(17, 14, 30),
(18, 15, 120),
(19, 16, 132),
(20, 17, 653),
(21, 18, 90),
(22, 19, 67),
(23, 20, 50),
(24, 21, 41),
(25, 22, 68),
(26, 23, 90),
(27, 24, 39),
(28, 25, 22),
(29, 26, 50),
(30, 27, 20),
(31, 28, 140),
(32, 29, 50),
(33, 30, 36),
(34, 31, 201),
(35, 32, 78),
(36, 33, 83),
(37, 34, 120),
(38, 35, 115),
(39, 36, 70),
(40, 37, 80),
(41, 38, 100),
(42, 39, 60),
(43, 40, 108),
(44, 41, 100),
(45, 42, 97),
(46, 43, 195),
(47, 44, 47),
(48, 45, 40),
(49, 46, 96),
(50, 47, 87),
(51, 48, 176);

--
-- Triggers `farm_inventory`
--
DELIMITER $$
CREATE TRIGGER `after_inventory_insert` AFTER INSERT ON `farm_inventory` FOR EACH ROW BEGIN
    UPDATE `farm_product`
    SET `quantity` = NEW.quantity_available
    WHERE `product_id` = NEW.product_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_inventory_update` AFTER UPDATE ON `farm_inventory` FOR EACH ROW BEGIN
    UPDATE `farm_product`
    SET `quantity` = NEW.quantity_available
    WHERE `product_id` = NEW.product_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `farm_product`
--

CREATE TABLE `farm_product` (
  `product_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `price_per_unit` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `rejection_reason` varchar(255) DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `approved_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `farm_product`
--

INSERT INTO `farm_product` (`product_id`, `farmer_id`, `category_id`, `product_name`, `description`, `product_image`, `unit`, `price_per_unit`, `quantity`, `status`, `rejection_reason`, `submitted_at`, `approved_at`, `approved_by`, `created_at`) VALUES
(1, 7029193, 1, 'Fresh Potato', 'Locally grown fresh potatoes', 'product_694dbd219fce8.png', 'kg', 45.00, 176, 'approved', NULL, '2025-12-20 15:14:01', '2025-12-26 04:39:29', 9999999, '2025-12-19 21:16:40'),
(2, 7029193, 1, 'Green Spinach', 'Organic leafy spinach', 'product_694e40f8d8ad6.jpg', 'bundle', 30.00, 59, 'approved', NULL, '2025-12-20 15:14:01', '2025-12-26 14:02:00', 9999999, '2025-12-19 21:16:40'),
(3, 7029193, 2, 'Banana', 'Naturally ripened banana', 'product_694e41c190743.jpg', 'dozen', 70.00, 40, 'approved', NULL, '2025-12-20 15:14:01', '2025-12-26 14:05:21', 9999999, '2025-12-19 21:16:40'),
(4, 7029193, 3, 'Miniket Rice', 'Fine quality miniket rice', 'product_694e417a7ab7c.jpg', 'kg', 68.00, 422, 'approved', NULL, '2025-12-20 15:14:01', '2025-12-26 14:04:10', 9999999, '2025-12-19 21:16:40'),
(5, 7502341, 1, 'Potato', 'Straight outta Munshiganj', 'product_694dc0d71bf5c.png', '5', 20.00, 2, 'approved', NULL, '2025-12-20 15:14:01', '2025-12-26 04:55:19', 9999999, '2025-12-19 21:16:40'),
(6, 7348129, 5, 'Milk', 'Rich with nutrients and vitamins.', 'product_694e416be5aff.jpg', '10', 70.00, 3, 'approved', NULL, '2025-12-20 15:14:01', '2025-12-26 14:03:55', 9999999, '2025-12-19 21:16:40'),
(7, 7099123, 3, 'Miniket Rice', 'Quality rice from the fields of manikganj', 'product_694e412d5bb4f.jpg', 'kg', 72.00, 200, 'approved', NULL, '2025-12-20 15:14:01', '2025-12-26 14:02:53', 9999999, '2025-12-19 21:16:40'),
(8, 7029193, 6, 'Hilsha', '', 'product_694dbd751232a.webp', 'kg', 1200.00, 134, 'approved', NULL, '2025-12-20 15:14:01', '2025-12-26 04:40:53', 9999999, '2025-12-20 11:14:39'),
(9, 7029193, 1, 'Cucumber', 'The best quality of Cucumber that is produced by Rumman Khan', 'product_6949908b3841c.jfif', 'kg', 35.00, 10, 'rejected', 'Picture not available', '2025-12-23 00:40:11', NULL, NULL, '2025-12-23 00:40:11'),
(10, 10000003, 8, 'Duck', 'Farm-raised ducks, known for their rich flavor and tender meat, perfect for any meal. Sourced from local farms, raised with care to ensure the highest quality.', 'product_694dbf633f61a.jpg', 'kg', 340.00, 14, 'approved', NULL, '2025-12-26 04:49:07', '2025-12-26 04:53:16', 9999999, '2025-12-26 04:49:07'),
(11, 10000003, 2, 'Orange', 'Sweet, juicy oranges packed with vitamins – perfect for a refreshing snack or fresh juice.', 'product_694dc023ebd73.webp', 'kg', 110.00, 14, 'approved', NULL, '2025-12-26 04:52:19', '2025-12-26 04:53:14', 9999999, '2025-12-26 04:52:19'),
(12, 7283944, 18, 'Beef', 'Fresh and Healthy', 'product_694dce3c479ab.jpg', 'kg', 750.00, 0, 'approved', NULL, '2025-12-26 05:52:28', '2025-12-26 05:53:55', 9999999, '2025-12-26 05:52:28'),
(13, 7283944, 1, 'Cauliflower', '', 'product_694dce800bdbe.jpg', 'piece', 40.00, 36, 'approved', NULL, '2025-12-26 05:53:36', '2025-12-26 05:53:57', 9999999, '2025-12-26 05:53:36'),
(14, 7283944, 8, 'Duck', 'Naturally Grown and Healthy', 'product_694e492f135ef.jpg', 'kg', 360.00, 30, 'approved', NULL, '2025-12-26 14:37:03', '2025-12-26 15:19:18', 9999999, '2025-12-26 14:37:03'),
(15, 7283944, 8, 'Eggs (Brown)', '', 'product_694e4e23e1367.webp', 'piece', 8.50, 120, 'approved', NULL, '2025-12-26 14:58:11', '2025-12-26 15:19:17', 9999999, '2025-12-26 14:58:11'),
(16, 7283944, 8, 'Eggs (White)', 'Fresh, high-quality white eggs. Ideal for cooking, baking, or enjoying as a protein-rich snack.', 'product_694e4eb368e0e.webp', 'piece', 7.00, 132, 'approved', NULL, '2025-12-26 15:00:35', '2025-12-26 15:19:15', 9999999, '2025-12-26 15:00:35'),
(17, 7300444, 5, 'Ghee', 'Fresh cream with a rich texture, used in making sweets, curries, or as a topping for desserts.', 'product_694e50e226614.jpg', 'kg', 800.00, 653, 'approved', NULL, '2025-12-26 15:09:54', '2025-12-26 15:19:13', 9999999, '2025-12-26 15:09:54'),
(18, 7300444, 5, 'Milk (Cow)', 'Full-fat buffalo milk, thicker and creamier than cow&#039;s milk. Ideal for making yoghurt, sweets, and traditional dishes.', 'product_694e51261abbf.jpg', 'liter', 70.00, 90, 'rejected', 'Not Fresh', '2025-12-26 15:11:02', NULL, NULL, '2025-12-26 15:11:02'),
(19, 7300444, 5, 'Milk (Buffalo)', 'Full-fat buffalo milk, thicker and creamier than cow&#039;s milk. Ideal for making yoghurt, sweets, and traditional dishes.', 'product_694e51a73d2aa.webp', 'liter', 90.00, 67, 'approved', NULL, '2025-12-26 15:13:11', '2025-12-26 15:18:44', 9999999, '2025-12-26 15:13:11'),
(20, 7300444, 5, 'Milk (Cow)', 'Fresh cow&#039;s milk, rich in calcium and protein. Perfect for drinking, tea, coffee, or making dairy products.', NULL, 'liter', 90.00, 50, 'rejected', 'No Photos Were Added', '2025-12-26 15:14:11', NULL, NULL, '2025-12-26 15:14:11'),
(21, 7300444, 5, 'Milk (Cow)', 'Fresh cow&#039;s milk, rich in calcium and protein. Perfect for drinking, tea, coffee, or making dairy products.', 'product_694e520cbc893.jpg', 'liter', 85.00, 41, 'approved', NULL, '2025-12-26 15:14:52', '2025-12-26 15:18:42', 9999999, '2025-12-26 15:14:52'),
(22, 7300444, 8, 'Eggs (Brown)', 'Nutritious brown eggs with a rich flavor.', 'product_694e52898726b.webp', 'piece', 8.00, 68, 'approved', NULL, '2025-12-26 15:16:57', '2025-12-26 15:18:39', 9999999, '2025-12-26 15:16:57'),
(23, 7300444, 8, 'Eggs (White)', 'Ideal for cooking, baking, or enjoying as a protein-rich snack.', 'product_694e52b8c17cb.webp', 'piece', 8.00, 90, 'approved', NULL, '2025-12-26 15:17:44', '2025-12-26 15:18:37', 9999999, '2025-12-26 15:17:44'),
(24, 7029193, 1, 'Carrot', 'Fresh and Imported from the village', 'product_694e637ca14a0.webp', 'kg', 50.00, 39, 'approved', NULL, '2025-12-26 16:29:16', '2025-12-26 16:36:57', 7601037, '2025-12-26 16:29:16'),
(25, 7029193, 1, 'Cauliflower', 'Fresh and imported from village', 'product_694ea33ee8311.jpg', 'piece', 42.00, 22, 'approved', NULL, '2025-12-26 21:01:18', '2025-12-26 21:05:28', 7601037, '2025-12-26 21:01:18'),
(26, 7431984, 8, 'Duck Meat', 'Rich, flavorful duck meat, perfect for traditional recipes. A unique taste that adds richness to any dish.', 'product_694eb53894c8d.jpg', 'kg', 560.00, 50, 'approved', NULL, '2025-12-26 22:18:00', '2025-12-26 22:46:48', 7601037, '2025-12-26 22:18:00'),
(27, 7431984, 6, 'Hilsa (Ilish Maach)', 'Premium Hilsa fish, a favorite in Bengali kitchens. Known for its rich flavor and soft texture.', 'product_694eb57674c78.webp', 'kg', 890.00, 20, 'approved', NULL, '2025-12-26 22:19:02', '2025-12-26 22:46:46', 7601037, '2025-12-26 22:19:02'),
(28, 7431984, 8, 'Eggs (Brown)', '', 'product_694eb5c288463.webp', 'piece', 8.00, 140, 'approved', NULL, '2025-12-26 22:20:18', '2025-12-26 22:46:45', 7601037, '2025-12-26 22:20:18'),
(29, 7431984, 8, 'Chicken (Layer)', 'Fresh layer chicken with firm texture and great taste. Ideal for various dishes, from stews to fried chicken.', 'product_694eb643a9431.jpg', 'kg', 300.00, 50, 'approved', NULL, '2025-12-26 22:22:27', '2025-12-26 22:46:43', 7601037, '2025-12-26 22:22:27'),
(30, 7431984, 6, 'Rui Fish (Rui Maach)', 'Fresh Rui fish, a delicacy in Bengali cuisine. Rich in protein, perfect for traditional Bengali recipes.', 'product_694eb6b8ed617.webp', 'kg', 220.00, 36, 'approved', NULL, '2025-12-26 22:24:24', '2025-12-26 22:46:42', 7601037, '2025-12-26 22:24:24'),
(31, 7029193, 6, 'Rui Fish (Rui Maach)', 'Fresh Rui fish, a delicacy in Bengali cuisine. Rich in protein, perfect for traditional Bengali recipes.', 'product_694eb7a8e65a0.webp', 'kg', 220.00, 201, 'approved', NULL, '2025-12-26 22:28:24', '2025-12-26 22:46:40', 7601037, '2025-12-26 22:28:24'),
(32, 7452199, 18, 'Beef (Boneless)', 'Tender boneless beef cuts, perfect for grilling, stews, or curries. High in protein, packed with flavor.', 'product_694eb8ef59389.jpg', 'kg', 600.00, 78, 'approved', NULL, '2025-12-26 22:33:51', '2025-12-26 22:46:39', 7601037, '2025-12-26 22:33:51'),
(33, 7452199, 18, 'Beef (Rib Cut)', 'Succulent beef rib cuts with a rich, hearty flavor. Perfect for barbecuing or slow-roasting to perfection.', 'product_694eb9365460f.jpg', 'kg', 650.00, 83, 'approved', NULL, '2025-12-26 22:35:02', '2025-12-26 22:46:37', 7601037, '2025-12-26 22:35:02'),
(34, 7452199, 8, 'Chicken (Layer)', 'Fresh layer chicken with firm texture and great taste. Ideal for various dishes, from stews to fried chicken.', 'product_694eb96ed21d5.jpg', 'kg', 280.00, 120, 'approved', NULL, '2025-12-26 22:35:58', '2025-12-26 22:46:36', 7601037, '2025-12-26 22:35:58'),
(35, 7452199, 8, 'Chicken (Local Deshi)', 'Fresh, juicy broiler chicken perfect for any recipe. Versatile for grilling, frying, or making hearty stews.', 'product_694eba4dc299e.jpg', 'kg', 280.00, 115, 'approved', NULL, '2025-12-26 22:39:41', '2025-12-26 22:46:33', 7601037, '2025-12-26 22:39:41'),
(36, 7348129, 4, 'Cumin', 'Aromatic cumin seeds, commonly used to enhance the flavor of curries, soups, and stews. Rich in antioxidants and adds a warm, earthy taste.', 'product_694ebf8dc5013.webp', 'kg', 400.00, 70, 'approved', NULL, '2025-12-26 23:02:05', '2025-12-26 23:13:17', 7601037, '2025-12-26 23:02:05'),
(37, 7348129, 1, 'Green Chili', 'Fresh, fiery green chillies that add a spicy kick to any dish', 'product_694ebfc01fb43.webp', 'kg', 80.00, 80, 'approved', NULL, '2025-12-26 23:02:56', '2025-12-26 23:13:15', 7601037, '2025-12-26 23:02:56'),
(38, 7348129, 1, 'Carrot', 'Fresh, crunchy carrots with a sweet and earthy flavor. Rich in vitamins and antioxidants, ideal for salads, juices, or as a snack', 'product_694ec019b6203.webp', 'kg', 55.00, 100, 'approved', NULL, '2025-12-26 23:04:25', '2025-12-26 23:13:14', 7601037, '2025-12-26 23:04:25'),
(39, 7348129, 6, 'Hilsa (Ilish Maach)', 'Premium Hilsa fish, a favorite in Bengali kitchens. Known for its rich flavor and soft texture.', 'product_694ec054d08d3.webp', 'kg', 800.00, 60, 'approved', NULL, '2025-12-26 23:05:24', '2025-12-26 23:13:14', 7601037, '2025-12-26 23:05:24'),
(40, 7348129, 6, 'Katla Fish (Katla Maach)', 'Delicious,big and tender Katla fish, ideal for curry and frying.', 'product_694ec0e5c0b3b.jpg', 'kg', 300.00, 108, 'approved', NULL, '2025-12-26 23:07:49', '2025-12-26 23:13:11', 7601037, '2025-12-26 23:07:49'),
(41, 7099123, 2, 'Mango (Langda)', 'Fresh from the farms of Rajshahi. Sweet and Savory.', 'product_694ed38435567.jpg', 'kg', 130.00, 100, 'approved', NULL, '2025-12-27 00:27:16', '2025-12-27 00:30:55', 9500101, '2025-12-27 00:27:16'),
(42, 7099123, 2, 'Apple', 'Australian variant grown in Naogaon.', 'product_694ed3ad8a434.jpg', 'kg', 200.00, 97, 'approved', NULL, '2025-12-27 00:27:57', '2025-12-27 00:30:54', 9500101, '2025-12-27 00:27:57'),
(43, 7099123, 3, 'BR28 Rice', 'Great for biryani and khichuri.', 'product_694ed4386cd1b.webp', 'kg', 55.00, 195, 'approved', NULL, '2025-12-27 00:30:16', '2025-12-27 00:30:52', 9500101, '2025-12-27 00:30:16'),
(44, 10000004, 8, 'Chicken (Broiler)', 'Cheap but fulfills the protein need of your family.', 'product_694ed65e9c5a3.jpg', 'kg', 180.00, 47, 'approved', NULL, '2025-12-27 00:39:26', '2025-12-27 00:47:37', 9500101, '2025-12-27 00:39:26'),
(45, 10000004, 8, 'Chicken (Layer)', 'Great choice for roasts and kormas!', 'product_694ed6ad78696.jpg', 'kg', 150.00, 40, 'approved', NULL, '2025-12-27 00:40:45', '2025-12-27 00:47:39', 9500101, '2025-12-27 00:40:45'),
(46, 10000004, 8, 'Chicken (Local Deshi)', 'Best quality chicken for curries!', 'product_694ed6e08f8cd.jpeg', 'kg', 270.00, 96, 'approved', NULL, '2025-12-27 00:41:36', '2025-12-27 00:47:38', 9500101, '2025-12-27 00:41:36'),
(47, 10000004, 8, 'Duck', 'We fed the ducks snails, so they are filled with nutrients!', 'product_694ed74429792.jpeg', 'kg', 400.00, 87, 'approved', NULL, '2025-12-27 00:43:16', '2025-12-27 00:47:35', 9500101, '2025-12-27 00:43:16'),
(48, 10000004, 8, 'Eggs (Brown)', 'Filled with all the vitamins!', 'product_694ed7d883740.jpg', 'piece', 8.00, 176, 'approved', NULL, '2025-12-27 00:45:44', '2025-12-27 00:47:34', 9500101, '2025-12-27 00:45:44');

-- --------------------------------------------------------

--
-- Table structure for table `government_prices`
--

CREATE TABLE `government_prices` (
  `price_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `category_id` int(11) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `effective_date` date DEFAULT curdate(),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `government_prices`
--

INSERT INTO `government_prices` (`price_id`, `product_name`, `category_id`, `price_per_unit`, `unit`, `effective_date`, `created_at`) VALUES
(1, 'Potato', 1, 30.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(2, 'Tomato', 1, 50.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(3, 'Onion', 1, 45.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(4, 'Spinach', 1, 25.00, 'bundle', '2025-12-19', '2025-12-19 21:16:40'),
(5, 'Cauliflower', 1, 40.00, 'piece', '2025-12-19', '2025-12-19 21:16:40'),
(6, 'Cabbage', 1, 35.00, 'piece', '2025-12-19', '2025-12-19 21:16:40'),
(7, 'Carrot', 1, 55.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(8, 'Brinjal', 1, 40.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(9, 'Cucumber', 1, 35.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(10, 'Green Chili', 1, 80.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(11, 'Mango', 2, 120.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(12, 'Banana', 2, 60.00, 'dozen', '2025-12-19', '2025-12-19 21:16:40'),
(13, 'Apple', 2, 200.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(14, 'Orange', 2, 100.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(15, 'Papaya', 2, 50.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(16, 'Guava', 2, 80.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(17, 'Miniket Rice', 3, 68.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(18, 'Nazirshail Rice', 3, 75.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(19, 'BR28 Rice', 3, 55.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(20, 'Wheat', 3, 45.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(21, 'Turmeric', 4, 200.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(22, 'Chili Powder', 4, 150.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(23, 'Cumin', 4, 400.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(24, 'Coriander', 4, 120.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(26, 'Ghee', 5, 800.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(27, 'Yogurt', 5, 80.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(28, 'Hilsha', 6, 1200.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(29, 'Rohu', 6, 300.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(30, 'Tilapia', 6, 180.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(31, 'Catla', 6, 280.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(35, 'Duck', 8, 350.00, 'kg', '2025-12-19', '2025-12-19 21:16:40'),
(36, 'Egg', 8, 12.00, 'piece', '2025-12-19', '2025-12-19 21:16:40'),
(37, 'Fresh Potato', 1, 40.00, 'kg', '2025-12-20', '2025-12-20 01:25:13'),
(38, 'Onion', 1, 60.00, 'kg', '2025-12-20', '2025-12-20 01:25:13'),
(39, 'Tomato', 1, 80.00, 'kg', '2025-12-20', '2025-12-20 01:25:13'),
(40, 'Green Spinach', 1, 30.00, 'bundle', '2025-12-20', '2025-12-20 01:25:13'),
(41, 'Cabbage', 1, 50.00, 'kg', '2025-12-20', '2025-12-20 01:25:13'),
(42, 'Carrot', 1, 70.00, 'kg', '2025-12-20', '2025-12-20 01:25:13'),
(43, 'Cauliflower', 1, 60.00, 'kg', '2025-12-20', '2025-12-20 01:25:13'),
(44, 'Banana', 2, 80.00, 'dozen', '2025-12-20', '2025-12-20 01:25:13'),
(45, 'Mango', 2, 150.00, 'kg', '2025-12-20', '2025-12-20 01:25:13'),
(46, 'Apple', 2, 250.00, 'kg', '2025-12-20', '2025-12-20 01:25:13'),
(47, 'Orange', 2, 120.00, 'kg', '2025-12-20', '2025-12-20 01:25:13'),
(48, 'Miniket Rice', 3, 75.00, 'kg', '2025-12-20', '2025-12-20 01:25:13'),
(49, 'Basmati Rice', 3, 120.00, 'kg', '2025-12-20', '2025-12-20 01:25:13'),
(50, 'Wheat', 3, 45.00, 'kg', '2025-12-20', '2025-12-20 01:25:13'),
(51, 'Milk', 4, 80.00, 'liter', '2025-12-20', '2025-12-20 01:25:13'),
(52, 'Egg', 5, 12.00, 'piece', '2025-12-20', '2025-12-20 01:25:13'),
(53, 'Chicken', 5, 220.00, 'kg', '2025-12-20', '2025-12-20 01:25:13'),
(54, 'Tilapia Fish', 6, 180.00, 'kg', '2025-12-20', '2025-12-20 01:25:13'),
(55, 'Rohu Fish', 6, 350.00, 'kg', '2025-12-20', '2025-12-20 01:25:13'),
(56, 'Mango (Langda)', 2, 150.00, '', '2025-12-26', '2025-12-26 14:51:05'),
(57, 'Guava (Amrapali)', 2, 80.00, '', '2025-12-26', '2025-12-26 14:51:05'),
(58, 'Banana (Kachamora)', 2, 50.00, '', '2025-12-26', '2025-12-26 14:51:05'),
(59, 'Pineapple (Golden Queen)', 2, 120.00, '', '2025-12-26', '2025-12-26 14:51:05'),
(60, 'Papaya (Red Lady)', 2, 70.00, '', '2025-12-26', '2025-12-26 14:51:05'),
(61, 'Rui Fish (Rui Maach)', 6, 220.00, '', '2025-12-26', '2025-12-26 14:51:05'),
(62, 'Katla Fish (Katla Maach)', 6, 240.00, '', '2025-12-26', '2025-12-26 14:51:05'),
(63, 'Hilsa (Ilish Maach)', 6, 800.00, '', '2025-12-26', '2025-12-26 14:51:05'),
(64, 'Tilapia Fish', 6, 150.00, '', '2025-12-26', '2025-12-26 14:51:05'),
(65, 'Pabda Fish (Pabda Maach)', 6, 300.00, '', '2025-12-26', '2025-12-26 14:51:05'),
(66, 'Beef (Boneless)', 18, 500.00, '', '2025-12-26', '2025-12-26 14:54:35'),
(67, 'Mutton (Goat Meat)', 18, 700.00, '', '2025-12-26', '2025-12-26 14:54:35'),
(68, 'Chicken (Broiler)', 8, 180.00, '', '2025-12-26', '2025-12-26 14:54:35'),
(69, 'Duck Meat', 8, 550.00, '', '2025-12-26', '2025-12-26 14:54:35'),
(70, 'Beef (Rib Cut)', 18, 600.00, '', '2025-12-26', '2025-12-26 14:54:35'),
(71, 'Chicken (Layer)', 8, 150.00, '', '2025-12-26', '2025-12-26 14:54:35'),
(72, 'Chicken (Local Deshi)', 8, 250.00, '', '2025-12-26', '2025-12-26 14:54:35'),
(73, 'Eggs (White)', 8, 7.00, '', '2025-12-26', '2025-12-26 14:54:35'),
(74, 'Eggs (Brown)', 8, 8.00, '', '2025-12-26', '2025-12-26 14:54:35'),
(75, 'Milk (Cow)', 5, 70.00, '', '2025-12-26', '2025-12-26 15:02:28'),
(76, 'Milk (Buffalo)', 5, 90.00, '', '2025-12-26', '2025-12-26 15:02:28'),
(77, 'Yoghurt (Plain)', 5, 120.00, '', '2025-12-26', '2025-12-26 15:02:28'),
(78, 'Cheese (Processed)', 5, 350.00, '', '2025-12-26', '2025-12-26 15:02:28'),
(79, 'Butter', 5, 450.00, '', '2025-12-26', '2025-12-26 15:02:28'),
(80, 'Ghee', 5, 700.00, '', '2025-12-26', '2025-12-26 15:02:28'),
(81, 'Paneer', 5, 250.00, '', '2025-12-26', '2025-12-26 15:02:28'),
(82, 'Cream', 5, 150.00, '', '2025-12-26', '2025-12-26 15:02:28'),
(83, 'Sweetened Yoghurt', 5, 150.00, '', '2025-12-26', '2025-12-26 15:02:28'),
(84, 'Milk Powder', 5, 500.00, '', '2025-12-26', '2025-12-26 15:02:28');

-- --------------------------------------------------------

--
-- Table structure for table `land`
--

CREATE TABLE `land` (
  `land_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `land_size` decimal(10,2) DEFAULT NULL,
  `soil_type` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `upazila` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_per_unit` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price_per_unit`) VALUES
(1, 2, 1, 5, 45.00),
(2, 3, 5, 1, 20.00),
(3, 4, 7, 3, 72.00),
(4, 4, 6, 2, 70.00),
(5, 4, 4, 2, 68.00),
(6, 5, 7, 1, 72.00),
(7, 6, 1, 1, 45.00),
(8, 7, 8, 2, 1200.00),
(9, 8, 12, 5, 750.00),
(10, 8, 16, 20, 7.00),
(11, 8, 17, 1, 800.00),
(12, 8, 8, 2, 1200.00),
(13, 8, 2, 2, 30.00),
(14, 8, 1, 2, 45.00),
(15, 8, 21, 5, 85.00),
(16, 9, 19, 5, 90.00),
(17, 9, 23, 14, 8.00),
(18, 9, 22, 12, 8.00),
(19, 9, 14, 5, 360.00),
(20, 9, 7, 6, 72.00),
(21, 9, 9, 6, 35.00),
(22, 9, 2, 5, 30.00),
(23, 9, 12, 10, 750.00),
(24, 9, 11, 5, 110.00),
(25, 10, 17, 2, 800.00),
(26, 10, 23, 12, 8.00),
(27, 10, 13, 4, 40.00),
(28, 10, 12, 4, 750.00),
(29, 10, 8, 4, 1200.00),
(30, 11, 11, 3, 110.00),
(31, 11, 24, 3, 50.00),
(32, 11, 19, 5, 90.00),
(33, 11, 16, 12, 7.00),
(34, 11, 12, 5, 750.00),
(35, 11, 4, 10, 68.00),
(36, 12, 14, 4, 360.00),
(37, 12, 19, 4, 90.00),
(38, 12, 17, 4, 800.00),
(39, 12, 3, 3, 70.00),
(40, 12, 12, 5, 750.00),
(41, 13, 4, 10, 68.00),
(42, 13, 35, 10, 280.00),
(43, 13, 33, 10, 650.00),
(44, 13, 32, 10, 600.00),
(45, 13, 17, 4, 800.00),
(46, 13, 30, 5, 220.00),
(47, 13, 21, 4, 85.00),
(48, 14, 35, 5, 280.00),
(49, 14, 31, 10, 220.00),
(50, 14, 28, 20, 8.00),
(51, 14, 30, 5, 220.00),
(52, 14, 24, 4, 50.00),
(53, 14, 23, 4, 8.00),
(54, 14, 12, 1, 750.00),
(55, 14, 8, 6, 1200.00),
(56, 14, 17, 3, 800.00),
(57, 15, 7, 20, 72.00),
(58, 15, 32, 5, 600.00),
(59, 15, 10, 6, 340.00),
(60, 15, 19, 8, 90.00),
(61, 15, 1, 21, 45.00),
(62, 15, 25, 6, 42.00),
(63, 16, 40, 21, 300.00),
(64, 16, 17, 30, 800.00),
(65, 16, 33, 15, 650.00),
(66, 16, 14, 6, 360.00),
(67, 16, 27, 5, 890.00),
(68, 16, 37, 10, 80.00),
(69, 16, 8, 10, 1200.00),
(70, 16, 32, 7, 600.00),
(71, 17, 40, 10, 300.00),
(72, 17, 30, 17, 220.00),
(73, 17, 36, 10, 400.00),
(74, 17, 17, 53, 800.00),
(75, 17, 31, 17, 220.00),
(76, 17, 4, 30, 68.00),
(77, 17, 3, 10, 70.00),
(78, 18, 17, 100, 800.00),
(79, 18, 40, 6, 300.00),
(80, 18, 19, 11, 90.00),
(81, 18, 33, 10, 650.00),
(82, 18, 4, 25, 68.00),
(83, 19, 48, 8, 8.00),
(84, 19, 43, 5, 55.00),
(85, 19, 37, 5, 80.00),
(86, 19, 47, 3, 400.00),
(87, 20, 48, 12, 8.00),
(88, 20, 47, 10, 400.00),
(89, 20, 46, 4, 270.00),
(90, 20, 44, 3, 180.00),
(91, 20, 42, 3, 200.00),
(92, 20, 40, 2, 300.00),
(93, 20, 31, 2, 220.00),
(94, 20, 2, 6, 30.00),
(95, 21, 40, 3, 300.00),
(96, 21, 33, 2, 650.00),
(97, 21, 25, 2, 42.00),
(98, 21, 24, 2, 50.00),
(99, 21, 48, 4, 8.00);

-- --------------------------------------------------------

--
-- Table structure for table `otp_verification`
--

CREATE TABLE `otp_verification` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `purpose` enum('registration','password_reset','login') DEFAULT 'registration',
  `expires_at` datetime NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `attempts` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `bkash_number` varchar(20) DEFAULT NULL,
  `payment_status` varchar(20) DEFAULT 'Paid',
  `payment_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `order_id`, `payment_method`, `bkash_number`, `payment_status`, `payment_time`) VALUES
(1, 5, 'bKash', '01907980386', 'Paid', '2025-12-17 19:34:54'),
(2, 6, 'cod', '', 'Paid', '2025-12-20 10:31:33'),
(3, 7, 'bkash', '01724252612', 'Paid', '2025-12-23 00:33:52'),
(4, 8, 'cod', '', 'Paid', '2025-12-26 15:25:19'),
(5, 9, 'bkash', '01854695498', 'Paid', '2025-12-26 15:32:41'),
(6, 4, 'Unknown', NULL, 'Paid', '2025-12-26 15:34:16'),
(7, 2, 'Unknown', NULL, 'Paid', '2025-12-26 15:35:49'),
(8, 3, 'Unknown', NULL, 'Paid', '2025-12-26 15:35:11'),
(9, 10, 'bkash', '01854695498', 'Paid', '2025-12-26 16:33:17'),
(10, 11, 'nagad', '01618570989', 'Paid', '2025-12-26 21:04:03'),
(11, 12, 'cod', '', 'Paid', '2025-12-26 22:08:34'),
(12, 13, 'bkash', '01618570989', 'Paid', '2025-12-26 22:54:05'),
(13, 14, 'cod', '', 'Paid', '2025-12-26 23:13:48'),
(14, 15, 'cod', '', 'Paid', '2025-12-26 23:13:59'),
(15, 16, 'bkash', '01618570989', 'Paid', '2025-12-26 23:19:02'),
(16, 17, 'bkash', '01618570989', 'Paid', '2025-12-26 23:21:54'),
(17, 18, 'bkash', '01618570989', 'Paid', '2025-12-26 23:26:28'),
(18, 19, 'cod', '', 'Paid', '2025-12-27 16:05:29'),
(19, 20, 'nagad', '01618570989', 'Paid', '2025-12-27 15:51:46'),
(20, 21, 'bkash', '01868630669', 'Paid', '2025-12-27 15:58:07');

-- --------------------------------------------------------

--
-- Table structure for table `pending_registration`
--

CREATE TABLE `pending_registration` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('Farmer','Customer') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `nid` varchar(50) DEFAULT NULL,
  `otp_code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport`
--

CREATE TABLE `transport` (
  `transport_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `farmer_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `pickup_location` varchar(150) DEFAULT NULL,
  `dropoff_location` varchar(150) DEFAULT NULL,
  `pickup_date` datetime DEFAULT NULL,
  `delivery_date` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `User_id` int(11) NOT NULL,
  `First_name` varchar(100) NOT NULL,
  `Last_name` varchar(100) NOT NULL,
  `Username` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `User_type` enum('Farmer','Customer','Admin') NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Email` varchar(150) DEFAULT NULL,
  `NID` varchar(20) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`User_id`, `First_name`, `Last_name`, `Username`, `Password`, `User_type`, `Phone`, `Email`, `NID`, `email_verified`, `last_login`) VALUES
(7029193, 'Riyad', 'Miah', 'riyadm', 'Riyad@44', 'Farmer', '01877889911', 'riyad.miah@gmail.com', '2001123445', 0, '2025-12-27 00:24:13'),
(7029344, 'Ahsan', 'Kabir', 'ahsan.k', 'Ahsan@111', 'Customer', '01722334455', 'ahsan.kabir@yahoo.com', '1991887345', 0, '2025-12-27 16:28:30'),
(7065411, 'Nusrat', 'Jahan', 'nusratj', 'Nusrat#55', 'Customer', '01555667722', 'nusrat.jahan@yahoo.com', '2001456782', 0, NULL),
(7099123, 'Javed', 'Hasan', 'javedh', 'Javed!112', 'Farmer', '01799001133', 'javed.hasan@gmail.com', '1989789034', 0, '2025-12-27 00:25:52'),
(7123988, 'Sultana', 'Begum', 'sultanab', 'Sultana303', 'Customer', '01766778812', 'sultana.begum@yahoo.com', '1990098712', 0, NULL),
(7159033, 'Mitu', 'Begum', 'mitubegum', 'Mitu@909', 'Customer', '01555667788', 'mitu.begum@yahoo.com', '1990456123', 0, NULL),
(7165321, 'Rakin', 'Hasib', 'rakin.h', 'Rakin*88', 'Customer', '01911223366', 'rakin.hasib@gmail.com', '1998876541', 0, NULL),
(7183440, 'Mariya', 'Sultana', 'mariyas', 'Mariya#12', 'Customer', '01988990033', 'mariya.sultana@gmail.com', '1995432178', 0, NULL),
(7203401, 'Tania', 'Sultana', 'tanias', 'Tania!21', 'Customer', '01799001122', 'tania.sultana@gmail.com', '2001567890', 0, NULL),
(7219033, 'Sabrina', 'Chowdhury', 'sabrinac', 'Sabrina#44', 'Customer', '01800112266', 'sabrina.chy@gmail.com', '2002876543', 0, NULL),
(7250934, 'Nahid', 'Hasan', 'nahidh', 'Nahid@13', 'Customer', '01988990044', 'nahid.hasan@yahoo.com', '2001345678', 0, '2025-12-26 23:08:40'),
(7283590, 'Sakib', 'Ahmed', 'sakibbd', 'Sakib098!', 'Customer', '01766778899', 'sakib.ahmed@gmail.com', '2002456781', 0, '2025-12-26 22:00:38'),
(7283944, 'Munna', 'Hossain', 'munnah', 'Munna@44', 'Farmer', '01555667711', 'munna.hossain@gmail.com', '1997894561', 0, '2025-12-27 16:01:40'),
(7300444, 'Nayeem', 'Islam', 'nayeemi', 'Nayeem008', 'Farmer', '01911223355', 'nayeem.islam@gmail.com', '1997890045', 0, '2025-12-26 15:07:43'),
(7331220, 'Mou', 'Akter', 'mouakter', 'Mou@303', 'Customer', '01944556688', 'mou.akter@yahoo.com', '1996684512', 0, '2025-12-26 21:02:15'),
(7338912, 'Rumana', 'Akter', 'rumana.bd', 'Rumana@22', 'Customer', '01833445599', 'rumana.akter@gmail.com', '1998056789', 0, '2025-12-27 15:54:57'),
(7348129, 'Abdul', 'Rahman', 'arahman', 'Abdul@123', 'Farmer', '01711223344', 'abdul.rahman@gmail.com', '1993456789', 0, '2025-12-26 23:01:04'),
(7412891, 'Saif', 'Rahman', 'saifrahman', 'Saif908!', 'Customer', '01911223377', 'saif.rahman@gmail.com', '1997654411', 0, '2025-12-26 23:16:13'),
(7419083, 'Lamia', 'Khan', 'lamiakhan', 'Lamia@19', 'Customer', '01800112277', 'lamia.khan@gmail.com', '1996587321', 0, NULL),
(7429834, 'Tamanna', 'Akter', 'tamannaa', 'Tamanna!10', 'Customer', '01833445588', 'tamanna.akter@gmail.com', '2001458791', 0, NULL),
(7431984, 'Omar', 'Faruk', 'omar.f', 'Omar2024', 'Farmer', '01799001155', 'omar.faruk@gmail.com', '1993123456', 0, '2025-12-26 22:16:16'),
(7440193, 'Jannat', 'Khanam', 'jannatk', 'Jannat@71', 'Customer', '01766778891', 'jannat.khanam@gmail.com', '1993456678', 0, '2025-12-27 00:48:24'),
(7450001, 'Arif', 'Hossain', 'arif.h', 'Arif@123', 'Customer', '01810000001', 'arif.hossain@gmail.com', '2001010001', 0, NULL),
(7450002, 'Rafi', 'Islam', 'rafi.i', 'Rafi@123', 'Customer', '01810000002', 'rafi.islam@gmail.com', '2001010002', 0, NULL),
(7450003, 'Niloy', 'Ahmed', 'niloy.a', 'Niloy@123', 'Customer', '01810000003', 'niloy.ahmed@gmail.com', '2001010003', 0, NULL),
(7450004, 'Shawon', 'Khan', 'shawon.k', 'Shawon@123', 'Customer', '01810000004', 'shawon.khan@gmail.com', '2001010004', 0, NULL),
(7450005, 'Tamim', 'Rahman', 'tamim.r', 'Tamim@123', 'Customer', '01810000005', 'tamim.rahman@gmail.com', '2001010005', 0, NULL),
(7450006, 'Sifat', 'Hasan', 'sifat.h', 'Sifat@123', 'Customer', '01810000006', 'sifat.hasan@gmail.com', '2001010006', 0, NULL),
(7450007, 'Adib', 'Mahmud', 'adib.m', 'Adib@123', 'Customer', '01810000007', 'adib.mahmud@gmail.com', '2001010007', 0, NULL),
(7450008, 'Fardin', 'Islam', 'fardin.i', 'Fardin@123', 'Customer', '01810000008', 'fardin.islam@gmail.com', '2001010008', 0, NULL),
(7450009, 'Nabil', 'Haque', 'nabil.h', 'Nabil@123', 'Customer', '01810000009', 'nabil.haque@gmail.com', '2001010009', 0, NULL),
(7450010, 'Ruhan', 'Chowdhury', 'ruhan.c', 'Ruhan@123', 'Customer', '01810000010', 'ruhan.ch@gmail.com', '2001010010', 0, NULL),
(7450011, 'Siam', 'Uddin', 'siam.u', 'Siam@123', 'Customer', '01810000011', 'siam.uddin@gmail.com', '2001010011', 0, NULL),
(7450012, 'Ayon', 'Mia', 'ayon.m', 'Ayon@123', 'Customer', '01810000012', 'ayon.mia@gmail.com', '2001010012', 0, NULL),
(7450013, 'Zihad', 'Islam', 'zihad.i', 'Zihad@123', 'Customer', '01810000013', 'zihad.islam@gmail.com', '2001010013', 0, NULL),
(7450014, 'Rakib', 'Hossain', 'rakib.h2', 'Rakib@123', 'Customer', '01810000014', 'rakib.h2@gmail.com', '2001010014', 0, NULL),
(7450015, 'Sajid', 'Khan', 'sajid.k', 'Sajid@123', 'Customer', '01810000015', 'sajid.khan@gmail.com', '2001010015', 0, NULL),
(7450016, 'Tanvir', 'Ahmed', 'tanvir.a', 'Tanvir@123', 'Customer', '01810000016', 'tanvir.ahmed@gmail.com', '2001010016', 0, NULL),
(7450017, 'Riad', 'Hasan', 'riad.h', 'Riad@123', 'Customer', '01810000017', 'riad.hasan@gmail.com', '2001010017', 0, NULL),
(7450018, 'Mahin', 'Islam', 'mahin.i', 'Mahin@123', 'Customer', '01810000018', 'mahin.islam@gmail.com', '2001010018', 0, NULL),
(7450019, 'Iftekhar', 'Rahman', 'iftekhar.r', 'Iftekhar@123', 'Customer', '01810000019', 'iftekhar.rahman@gmail.com', '2001010019', 0, NULL),
(7450020, 'Anik', 'Sarkar', 'anik.s', 'Anik@123', 'Customer', '01810000020', 'anik.sarkar@gmail.com', '2001010020', 0, NULL),
(7450021, 'Shihab', 'Uddin', 'shihab.u', 'Shihab@123', 'Customer', '01810000021', 'shihab.uddin@gmail.com', '2001010021', 0, NULL),
(7450022, 'Foysal', 'Mia', 'foysal.m', 'Foysal@123', 'Customer', '01810000022', 'foysal.mia@gmail.com', '2001010022', 0, NULL),
(7450023, 'Ashik', 'Hassan', 'ashik.h', 'Ashik@123', 'Customer', '01810000023', 'ashik.hassan@gmail.com', '2001010023', 0, '2025-12-27 15:49:56'),
(7450024, 'Rony', 'Islam', 'rony.i', 'Rony@123', 'Customer', '01810000024', 'rony.islam@gmail.com', '2001010024', 0, NULL),
(7450025, 'Jubayer', 'Ahmed', 'jubayer.a', 'Jubayer@123', 'Customer', '01810000025', 'jubayer.ahmed@gmail.com', '2001010025', 0, NULL),
(7450026, 'Sabbir', 'Rahman', 'sabbir.r2', 'Sabbir@123', 'Customer', '01810000026', 'sabbir.rahman@gmail.com', '2001010026', 0, NULL),
(7450027, 'Rasel', 'Hossain', 'rasel.h2', 'Rasel@123', 'Customer', '01810000027', 'rasel.h2@gmail.com', '2001010027', 0, NULL),
(7450028, 'Arafat', 'Islam', 'arafat.i2', 'Arafat@123', 'Customer', '01810000028', 'arafat.islam@gmail.com', '2001010028', 0, NULL),
(7450029, 'Sohan', 'Khan', 'sohan.k2', 'Sohan@123', 'Customer', '01810000029', 'sohan.khan@gmail.com', '2001010029', 0, NULL),
(7450030, 'Minhaz', 'Rahman', 'minhaz.r', 'Minhaz@123', 'Customer', '01810000030', 'minhaz.rahman@gmail.com', '2001010030', 0, NULL),
(7450031, 'Riyad', 'Hasan', 'riyad.h2', 'Riyad@123', 'Customer', '01810000031', 'riyad.hasan@gmail.com', '2001010031', 0, NULL),
(7450032, 'Fahad', 'Islam', 'fahad.i', 'Fahad@123', 'Customer', '01810000032', 'fahad.islam@gmail.com', '2001010032', 0, NULL),
(7450033, 'Sakib', 'Ahmed', 'sakib.a2', 'Sakib@123', 'Customer', '01810000033', 'sakib.ahmed@gmail.com', '2001010033', 0, NULL),
(7450034, 'Imtiaz', 'Hossain', 'imtiaz.h', 'Imtiaz@123', 'Customer', '01810000034', 'imtiaz.hossain@gmail.com', '2001010034', 0, NULL),
(7450035, 'Shanto', 'Islam', 'shanto.i', 'Shanto@123', 'Customer', '01810000035', 'shanto.islam@gmail.com', '2001010035', 0, NULL),
(7450036, 'Noman', 'Khan', 'noman.k', 'Noman@123', 'Customer', '01810000036', 'noman.khan@gmail.com', '2001010036', 0, NULL),
(7450037, 'Ashraf', 'Uddin', 'ashraf.u', 'Ashraf@123', 'Customer', '01810000037', 'ashraf.uddin@gmail.com', '2001010037', 0, NULL),
(7450038, 'Tuhin', 'Mia', 'tuhin.m', 'Tuhin@123', 'Customer', '01810000038', 'tuhin.mia@gmail.com', '2001010038', 0, NULL),
(7450039, 'Parvez', 'Hasan', 'parvez.h', 'Parvez@123', 'Customer', '01810000039', 'parvez.hasan@gmail.com', '2001010039', 0, NULL),
(7450040, 'Mahfuz', 'Rahman', 'mahfuz.r', 'Mahfuz@123', 'Customer', '01810000040', 'mahfuz.rahman@gmail.com', '2001010040', 0, NULL),
(7450041, 'Raihan', 'Islam', 'raihan.i2', 'Raihan@123', 'Customer', '01810000041', 'raihan.islam@gmail.com', '2001010041', 0, NULL),
(7450042, 'Shamim', 'Ahmed', 'shamim.a', 'Shamim@123', 'Customer', '01810000042', 'shamim.ahmed@gmail.com', '2001010042', 0, NULL),
(7450043, 'Emon', 'Hossain', 'emon.h', 'Emon@123', 'Customer', '01810000043', 'emon.hossain@gmail.com', '2001010043', 0, NULL),
(7450044, 'Jahid', 'Islam', 'jahid.i', 'Jahid@123', 'Customer', '01810000044', 'jahid.islam@gmail.com', '2001010044', 0, NULL),
(7450045, 'Rifat', 'Khan', 'rifat.k2', 'Rifat@123', 'Customer', '01810000045', 'rifat.khan@gmail.com', '2001010045', 0, NULL),
(7450046, 'Masud', 'Rahman', 'masud.r', 'Masud@123', 'Customer', '01810000046', 'masud.rahman@gmail.com', '2001010046', 0, NULL),
(7450047, 'Bashir', 'Uddin', 'bashir.u', 'Bashir@123', 'Customer', '01810000047', 'bashir.uddin@gmail.com', '2001010047', 0, NULL),
(7450048, 'Shafi', 'Islam', 'shafi.i', 'Shafi@123', 'Customer', '01810000048', 'shafi.islam@gmail.com', '2001010048', 0, NULL),
(7450049, 'Tareq', 'Hasan', 'tareq.h', 'Tareq@123', 'Customer', '01810000049', 'tareq.hasan@gmail.com', '2001010049', 0, NULL),
(7450050, 'Rashed', 'Khan', 'rashed.k', 'Rashed@123', 'Customer', '01810000050', 'rashed.khan@gmail.com', '2001010050', 0, NULL),
(7450051, 'Naim', 'Islam', 'naim.i', 'Naim@123', 'Customer', '01810000051', 'naim.islam@gmail.com', '2001010051', 0, NULL),
(7450052, 'Firoz', 'Ahmed', 'firoz.a', 'Firoz@123', 'Customer', '01810000052', 'firoz.ahmed@gmail.com', '2001010052', 0, NULL),
(7450053, 'Iqbal', 'Hossain', 'iqbal.h', 'Iqbal@123', 'Customer', '01810000053', 'iqbal.hossain@gmail.com', '2001010053', 0, NULL),
(7450054, 'Nazmul', 'Islam', 'nazmul.i', 'Nazmul@123', 'Customer', '01810000054', 'nazmul.islam@gmail.com', '2001010054', 0, NULL),
(7450055, 'Kawsar', 'Khan', 'kawsar.k', 'Kawsar@123', 'Customer', '01810000055', 'kawsar.khan@gmail.com', '2001010055', 0, NULL),
(7450056, 'Sujon', 'Mia', 'sujon.m', 'Sujon@123', 'Customer', '01810000056', 'sujon.mia@gmail.com', '2001010056', 0, NULL),
(7450057, 'Habib', 'Rahman', 'habib.r', 'Habib@123', 'Customer', '01810000057', 'habib.rahman@gmail.com', '2001010057', 0, NULL),
(7450058, 'Alif', 'Hasan', 'alif.h', 'Alif@123', 'Customer', '01810000058', 'alif.hasan@gmail.com', '2001010058', 0, NULL),
(7450059, 'Rokon', 'Uddin', 'rokon.u', 'Rokon@123', 'Customer', '01810000059', 'rokon.uddin@gmail.com', '2001010059', 0, NULL),
(7450060, 'Jony', 'Islam', 'jony.i', 'Jony@123', 'Customer', '01810000060', 'jony.islam@gmail.com', '2001010060', 0, NULL),
(7450061, 'Sazzad', 'Khan', 'sazzad.k', 'Sazzad@123', 'Customer', '01810000061', 'sazzad.khan@gmail.com', '2001010061', 0, NULL),
(7450062, 'Shawon', 'Ahmed', 'shawon.a2', 'Shawon@123', 'Customer', '01810000062', 'shawon.ahmed@gmail.com', '2001010062', 0, NULL),
(7450063, 'Mahdi', 'Islam', 'mahdi.i', 'Mahdi@123', 'Customer', '01810000063', 'mahdi.islam@gmail.com', '2001010063', 0, NULL),
(7450064, 'Zakir', 'Hossain', 'zakir.h', 'Zakir@123', 'Customer', '01810000064', 'zakir.hossain@gmail.com', '2001010064', 0, NULL),
(7450065, 'Sami', 'Rahman', 'sami.r', 'Sami@123', 'Customer', '01810000065', 'sami.rahman@gmail.com', '2001010065', 0, NULL),
(7450066, 'Tanim', 'Islam', 'tanim.i2', 'Tanim@123', 'Customer', '01810000066', 'tanim.islam@gmail.com', '2001010066', 0, NULL),
(7452199, 'Fahim', 'Rahman', 'frahman', 'Fahim2024', 'Farmer', '01800112233', 'fahim.rahman@yahoo.com', '1999678901', 0, '2025-12-26 22:31:47'),
(7500001, 'Karim', 'Miah', 'karim.m', 'Farm#001', 'Farmer', '01710000001', 'karim.miah@gmail.com', '1987000001', 0, NULL),
(7500002, 'Salam', 'Uddin', 'salam.u', 'Farm#002', 'Farmer', '01710000002', 'salam.uddin@gmail.com', '1987000002', 0, NULL),
(7500004, 'Jalal', 'Ahmed', 'jalal.a', 'Farm#004', 'Farmer', '01710000004', 'jalal.ahmed@gmail.com', '1987000004', 0, NULL),
(7500006, 'Abdul', 'Karim', 'abdul.k', 'Farm#006', 'Farmer', '01710000006', 'abdul.karim@gmail.com', '1987000006', 0, NULL),
(7500007, 'Rahim', 'Sheikh', 'rahim.s', 'Farm#007', 'Farmer', '01710000007', 'rahim.sheikh@gmail.com', '1987000007', 0, NULL),
(7500008, 'Kader', 'Hossain', 'kader.h', 'Farm#008', 'Farmer', '01710000008', 'kader.hossain@gmail.com', '1987000008', 0, NULL),
(7500009, 'Mofiz', 'Uddin', 'mofiz.u', 'Farm#009', 'Farmer', '01710000009', 'mofiz.uddin@gmail.com', '1987000009', 0, NULL),
(7500010, 'Anwar', 'Miah', 'anwar.m', 'Farm#010', 'Farmer', '01710000010', 'anwar.miah@gmail.com', '1987000010', 0, NULL),
(7500011, 'Hasan', 'Faruk', 'hasan.f', 'Farm#011', 'Farmer', '01710000011', 'hasan.faruk@gmail.com', '1987000011', 0, NULL),
(7500015, 'Nur', 'Alam', 'nur.a', 'Farm#015', 'Farmer', '01710000015', 'nur.alam@gmail.com', '1987000015', 0, NULL),
(7502341, 'Monir', 'Uddin', 'monir.bd', 'Monir@778', 'Farmer', '01944556677', 'monir.uddin@gmail.com', '1978456120', 0, NULL),
(7532190, 'Ridoy', 'Hasan', 'ridoyh', 'Ridoy@71', 'Farmer', '01988990055', 'ridoy.hasan@gmail.com', '1990876543', 0, NULL),
(7569211, 'Ishrat', 'Jahan', 'ishratj', 'Ishrat#66', 'Customer', '01944556699', 'ishrat.jahan@yahoo.com', '1991345678', 0, NULL),
(7600001, 'Amina', 'Khatun', 'amina.k', 'User#01', 'Customer', '01820000001', 'amina.khatun@gmail.com', '2001000001', 0, NULL),
(7600002, 'Rohan', 'Ahmed', 'rohan.a', 'User#02', 'Customer', '01820000002', 'rohan.ahmed@gmail.com', '2001000002', 0, NULL),
(7600003, 'Sadia', 'Islam', 'sadia.i', 'User#03', 'Customer', '01820000003', 'sadia.islam@gmail.com', '2001000003', 0, NULL),
(7600004, 'Imran', 'Hossain', 'imran.h', 'User#04', 'Customer', '01820000004', 'imran.hossain@gmail.com', '2001000004', 0, '2025-12-26 23:19:56'),
(7600005, 'Nabila', 'Khan', 'nabila.k', 'User#05', 'Customer', '01820000005', 'nabila.khan@gmail.com', '2001000005', 0, NULL),
(7600006, 'Saif', 'Uddin', 'saif.u', 'User#06', 'Customer', '01820000006', 'saif.uddin@gmail.com', '2001000006', 0, NULL),
(7600007, 'Farhan', 'Miah', 'farhan.m', 'User#07', 'Customer', '01820000007', 'farhan.miah@gmail.com', '2001000007', 0, NULL),
(7600008, 'Lamia', 'Chowdhury', 'lamia.c', 'User#08', 'Customer', '01820000008', 'lamia.chowdhury@gmail.com', '2001000008', 0, NULL),
(7600009, 'Arif', 'Rahman', 'arif.r', 'User#09', 'Customer', '01820000009', 'arif.rahman@gmail.com', '2001000009', 0, NULL),
(7600010, 'Tania', 'Sultana', 'tania.s', 'User#10', 'Customer', '01820000010', 'tania.sultana@gmail.com', '2001000010', 0, NULL),
(7600011, 'Mehedi', 'Hasan', 'mehedi.h', 'User#11', 'Customer', '01820000011', 'mehedi.hasan@gmail.com', '2001000011', 0, NULL),
(7600012, 'Jarin', 'Akter', 'jarin.a', 'User#12', 'Customer', '01820000012', 'jarin.akter@gmail.com', '2001000012', 0, NULL),
(7600013, 'Omar', 'Faruk', 'omar_faruk', 'User#13', 'Customer', '01820000013', 'omar.faruk@gmail.com', '2001000013', 0, NULL),
(7600014, 'Maliha', 'Tasnim', 'maliha.t', 'User#14', 'Customer', '01820000014', 'maliha.tasnim@gmail.com', '2001000014', 0, '2025-12-27 14:58:14'),
(7600015, 'Ridwan', 'Khan', 'ridwan.k', 'User#15', 'Customer', '01820000015', 'ridwan.khan@gmail.com', '2001000015', 0, NULL),
(7600016, 'Sakib', 'Alam', 'sakib.a', 'User#16', 'Customer', '01820000016', 'sakib.alam@gmail.com', '2001000016', 0, NULL),
(7600017, 'Nusrat', 'Jahan', 'nusrat.j', 'User#17', 'Customer', '01820000017', 'nusrat.jahan@gmail.com', '2001000017', 0, NULL),
(7600018, 'Tahmid', 'Ahmed', 'tahmid.a', 'User#18', 'Customer', '01820000018', 'tahmid.ahmed@gmail.com', '2001000018', 0, '2025-12-26 23:25:34'),
(7600019, 'Mim', 'Akter', 'mim.a', 'User#19', 'Customer', '01820000019', 'mim.akter@gmail.com', '2001000019', 0, NULL),
(7600020, 'Zayed', 'Hossain', 'zayed.h', 'User#20', 'Customer', '01820000020', 'zayed.hossain@gmail.com', '2001000020', 0, NULL),
(7600021, 'Afsana', 'Miah', 'afsana.m', 'User#21', 'Customer', '01820000021', 'afsana.miah@gmail.com', '2001000021', 0, NULL),
(7600022, 'Rayhan', 'Kabir', 'rayhan.k', 'User#22', 'Customer', '01820000022', 'rayhan.kabir@gmail.com', '2001000022', 0, NULL),
(7600023, 'Shifa', 'Tasnim', 'shifa.t', 'User#23', 'Customer', '01820000023', 'shifa.tasnim@gmail.com', '2001000023', 0, NULL),
(7600024, 'Abrar', 'Rahman', 'abrar.r', 'User#24', 'Customer', '01820000024', 'abrar.rahman@gmail.com', '2001000024', 0, NULL),
(7600025, 'Sohana', 'Islam', 'sohana.i', 'User#25', 'Customer', '01820000025', 'sohana.islam@gmail.com', '2001000025', 0, NULL),
(7600026, 'Ehsan', 'Khan', 'ehsan.k', 'User#26', 'Customer', '01820000026', 'ehsan.khan@gmail.com', '2001000026', 0, NULL),
(7600027, 'Raisa', 'Khatun', 'raisa.k', 'User#27', 'Customer', '01820000027', 'raisa.khatun@gmail.com', '2001000027', 0, NULL),
(7600028, 'Mahin', 'Ahmed', 'mahin.a', 'User#28', 'Customer', '01820000028', 'mahin.ahmed@gmail.com', '2001000028', 0, NULL),
(7600029, 'Liza', 'Akter', 'liza.a', 'User#29', 'Customer', '01820000029', 'liza.akter@gmail.com', '2001000029', 0, NULL),
(7600030, 'Jawad', 'Hasan', 'jawad.h', 'User#30', 'Customer', '01820000030', 'jawad.hasan@gmail.com', '2001000030', 0, NULL),
(7600031, 'Nayla', 'Jahan', 'nayla.j', 'User#31', 'Customer', '01820000031', 'nayla.jahan@gmail.com', '2001000031', 0, NULL),
(7600032, 'Ratul', 'Islam', 'ratul.i', 'User#32', 'Customer', '01820000032', 'ratul.islam@gmail.com', '2001000032', 0, NULL),
(7600033, 'Iffat', 'Sultana', 'iffat.s', 'User#33', 'Customer', '01820000033', 'iffat.sultana@gmail.com', '2001000033', 0, NULL),
(7600034, 'Siam', 'Khan', 'siam.k', 'User#34', 'Customer', '01820000034', 'siam.khan@gmail.com', '2001000034', 0, NULL),
(7600035, 'Maira', 'Tasnim', 'maira.t', 'User#35', 'Customer', '01820000035', 'maira.tasnim@gmail.com', '2001000035', 0, NULL),
(7600036, 'Ayan', 'Faruk', 'ayan.f', 'User#36', 'Customer', '01820000036', 'ayan.faruk@gmail.com', '2001000036', 0, NULL),
(7600037, 'Nafisa', 'Khatun', 'nafisa.k', 'User#37', 'Customer', '01820000037', 'nafisa.khatun@gmail.com', '2001000037', 0, NULL),
(7600038, 'Labib', 'Ahmed', 'labib.a', 'User#38', 'Customer', '01820000038', 'labib.ahmed@gmail.com', '2001000038', 0, NULL),
(7600039, 'Zara', 'Khan', 'zara.k', 'User#39', 'Customer', '01820000039', 'zara.khan@gmail.com', '2001000039', 0, NULL),
(7600040, 'Fahad', 'Rahman', 'fahad.r', 'User#40', 'Customer', '01820000040', 'fahad.rahman@gmail.com', '2001000040', 0, NULL),
(7601037, 'Rumman', 'Khan', 'rkchamp', 'rk123', 'Admin', '01868630669', 'rumman@gmail.com', '8001500001', 0, '2025-12-27 15:59:19'),
(7634891, 'Shila', 'Akter', 'shilaakter', 'Shila!999', 'Customer', '01555667789', 'shila.akter@yahoo.com', '1990561234', 0, NULL),
(7644433, 'Sadia', 'Ferdous', 'sadiaf', 'Sadia#88', 'Customer', '01722334466', 'sadia.ferdous@yahoo.com', '1989654323', 0, NULL),
(7648201, 'Sumaiya', 'Khatun', 'sumaiyak', 'Sumaiya89', 'Customer', '01911223344', 'sumaiya.khatun@gmail.com', '2002987654', 0, NULL),
(7701893, 'Shamim', 'Reza', 'shamimr', 'Shamim505', 'Customer', '01799001166', 'shamim.reza@gmail.com', '2001345987', 0, NULL),
(7732091, 'Niloy', 'Karim', 'niloyk', 'Niloy2025', 'Customer', '01722334477', 'niloy.karim@yahoo.com', '1992567811', 0, NULL),
(7812393, 'Sohana', 'Sultana', 'sohana.s', 'Sohana!11', 'Customer', '01877889944', 'sohana.sultana@yahoo.com', '1999056781', 0, NULL),
(7829891, 'Rasel', 'Ahmed', 'rasel.bd', 'Rasel!22', 'Customer', '01555667790', 'rasel.ahmed@gmail.com', '2002123490', 0, NULL),
(7845990, 'Mamun', 'Chowdhury', 'mamun.c', 'Mamun2025', 'Customer', '01766778890', 'mamun.chowdhury@gmail.com', '1998987612', 0, NULL),
(7882345, 'Farzana', 'Yasmin', 'farzanay', 'Farzana@10', 'Customer', '01800112244', 'farzana.yasmin@gmail.com', '2001987600', 0, NULL),
(7932441, 'Sharmin', 'Akhter', 'sakhter', 'Sharmin!55', 'Customer', '01933445566', 'sharmin.akhter@yahoo.com', '2001346789', 0, NULL),
(7940012, 'Arif', 'Mahmud', 'arifm', 'Arif@333', 'Customer', '01766778823', 'arif.mahmud@gmail.com', '1992786541', 0, NULL),
(7993544, 'Rafi', 'Islam', 'rafiislm', 'Rafi#009', 'Customer', '01833445566', 'rafi.islam@gmail.com', '1992678910', 0, NULL),
(8124593, 'Hasan', 'Khan', 'hkhan', 'Hasan#2025', 'Customer', '01822334455', 'hasan.khan@gmail.com', '1987654321', 0, NULL),
(9500101, 'Abdullah', 'Ifaz', 'ifaz_AFC', 'londonisred', 'Admin', '0000000001', 'ifaz@mail.com', '0200000000', 0, '2025-12-27 00:47:29'),
(9500102, 'Nausicaa', 'Ren', 'renn', 'ifaz', 'Customer', '011231231', 'rn@gmail.com', '123456', 0, NULL),
(9500105, 'Nausicaaa', 'Rren', 'meanw', 'ifaz', 'Customer', '011231231', 'rn@gmail.com', '123456', 0, NULL),
(9999999, 'Ahnaf', 'Nehal', 'ahnafnehal_', 'halamadrid', 'Admin', '0000000000', 'admin@agrisphere.com', '0000000000', 0, '2025-12-23 00:54:31'),
(10000000, 'Ahnaf', 'Nehal', 'ahnafneh_al', '123456', 'Customer', '01761289072', 'orpatasnimejannat@gmail.com', '9966244506', 1, '2025-12-20 15:44:34'),
(10000002, 'Mumtahina', 'Naomi', 'Naomi_', '1234567', 'Customer', '01724252612', 'naomi@gmail.com', '4438267484', 1, '2025-12-23 00:32:58'),
(10000003, 'Karim', 'Uddin', 'karim22', 'karim123', 'Farmer', '01915485', 'karim@gmail.com', '3660340212', 1, '2025-12-26 04:45:28'),
(10000004, 'Ratul', 'Hassan', 'ratulh', 'ratul123', 'Farmer', '01010101010', 'ratul@gmail.com', '7297171110', 1, '2025-12-27 17:40:34'),
(10000005, 'Fatiha', 'Khan', 'fatihakhan', 'fatiha123', 'Farmer', '01915485', 'fatiha@gmail.com', '7399612203', 1, '2025-12-27 15:53:58');

--
-- Triggers `user`
--
DELIMITER $$
CREATE TRIGGER `after_user_insert` AFTER INSERT ON `user` FOR EACH ROW BEGIN
   
    IF NEW.User_type = 'Customer' THEN
        INSERT INTO customer (customer_id, address, phone)
        VALUES (
            NEW.User_id,
            CONCAT(
                'House-', FLOOR(1 + RAND() * 200),
                ', Road-', FLOOR(1 + RAND() * 60),
                ', Block-', CHAR(FLOOR(65 + (RAND() * 6))),
                ', Union/Ward-', FLOOR(1 + RAND() * 20),
                ', ',
                CASE FLOOR(1 + (RAND() * 10))
                    WHEN 1 THEN 'Dhanmondi, Dhaka-1209'
                    WHEN 2 THEN 'Gulshan-2, Dhaka-1212'
                    WHEN 3 THEN 'Mirpur-10, Dhaka-1216'
                    WHEN 4 THEN 'Uttara Sector-4, Dhaka-1230'
                    WHEN 5 THEN 'Kotwali, Chattogram-4000'
                    WHEN 6 THEN 'Pahartali, Chattogram-4212'
                    WHEN 7 THEN 'Ambarkhana, Sylhet-3100'
                    WHEN 8 THEN 'Rajshahi Sadar, Rajshahi-6000'
                    WHEN 9 THEN 'Khulna Sadar, Khulna-9000'
                    WHEN 10 THEN 'Barishal Sadar, Barishal-8200'
                END
            ),
            NEW.Phone
        );
    END IF;

    IF NEW.User_type = 'Farmer' THEN
        INSERT INTO farmer (farmer_id, address, bank_name, bank_account_number)
        VALUES (
            NEW.User_id,

         
            CONCAT(
                'House-', FLOOR(1 + RAND() * 200),
                ', Road-', FLOOR(1 + RAND() * 60),
                ', Village ',
                ELT(
                    FLOOR(1 + RAND() * 8),
                    'Charpara', 'Belkuchi', 'Bheramara', 'Kaliganj',
                    'Fenchuganj', 'Fulbari', 'Matlab', 'Mirzapur'
                ),
                ', Union ',
                ELT(
                    FLOOR(1 + RAND() * 8),
                    'Sadar', 'Belkuchi', 'Bheramara', 'Kalihati',
                    'Bagha', 'Fenchuganj', 'Fulbari', 'Matlab'
                ),
                ', Upazila ',
                ELT(
                    FLOOR(1 + RAND() * 8),
                    'Sadar', 'Belkuchi', 'Bheramara', 'Kaliganj',
                    'Bagha', 'Fenchuganj', 'Fulbari', 'Matlab'
                ),
                ', District ',
                ELT(
                    FLOOR(1 + RAND() * 10),
                    'Dhaka', 'Gazipur', 'Cumilla', 'Chattogram',
                    'Rajshahi', 'Kushtia', 'Tangail', 'Sylhet',
                    'Dinajpur', 'Chandpur'
                ),
                ', Bangladesh'
            ),

            
            ELT(
                FLOOR(1 + RAND() * 7),
                'Sonali Bank Limited',
                'Janata Bank Limited',
                'Agrani Bank Limited',
                'Rupali Bank Limited',
                'Islami Bank Bangladesh Limited',
                'Dutch-Bangla Bank Limited',
                'BRAC Bank Limited'
            ),

          
            CONCAT('AC-', LPAD(FLOOR(RAND() * 10000000000), 10, '0'))
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `set_admin_role` BEFORE INSERT ON `user` FOR EACH ROW BEGIN
    IF NEW.Username = 'admin' THEN
        SET NEW.User_type = 'Admin';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_block`
--

CREATE TABLE `user_block` (
  `user_id` int(11) NOT NULL,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 1,
  `blocked_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_block`
--

INSERT INTO `user_block` (`user_id`, `is_blocked`, `blocked_at`) VALUES
(7532190, 1, '2025-12-27 14:55:30'),
(7600035, 1, '2025-12-27 15:42:30'),
(7644433, 0, '2025-12-17 00:58:35'),
(7701893, 1, '2025-12-27 15:42:24'),
(9500102, 1, '2025-12-26 21:05:49'),
(9500105, 0, '2025-12-26 16:36:19'),
(10000002, 0, '2025-12-23 00:53:50');

-- --------------------------------------------------------

--
-- Table structure for table `warehouse`
--

CREATE TABLE `warehouse` (
  `warehouse_id` int(11) NOT NULL,
  `warehouse_name` varchar(150) NOT NULL,
  `district` varchar(100) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `warehouse_stock`
--

CREATE TABLE `warehouse_stock` (
  `ws_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `arrival_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `ai_log`
--
ALTER TABLE `ai_log`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `customer_cart`
--
ALTER TABLE `customer_cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `fk_cart_customer` (`customer_id`),
  ADD KEY `fk_cart_product` (`product_id`);

--
-- Indexes for table `customer_order`
--
ALTER TABLE `customer_order`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_order_customer` (`customer_id`);

--
-- Indexes for table `farmer`
--
ALTER TABLE `farmer`
  ADD PRIMARY KEY (`farmer_id`);

--
-- Indexes for table `farm_inventory`
--
ALTER TABLE `farm_inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD KEY `fk_inventory_product` (`product_id`);

--
-- Indexes for table `farm_product`
--
ALTER TABLE `farm_product`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `fk_product_farmer` (`farmer_id`),
  ADD KEY `fk_product_category` (`category_id`);

--
-- Indexes for table `government_prices`
--
ALTER TABLE `government_prices`
  ADD PRIMARY KEY (`price_id`),
  ADD KEY `fk_govprice_category` (`category_id`);

--
-- Indexes for table `land`
--
ALTER TABLE `land`
  ADD PRIMARY KEY (`land_id`),
  ADD KEY `fk_land_farmer` (`farmer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `fk_orderitem_order` (`order_id`),
  ADD KEY `fk_orderitem_product` (`product_id`);

--
-- Indexes for table `otp_verification`
--
ALTER TABLE `otp_verification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_otp_code` (`otp_code`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `pending_registration`
--
ALTER TABLE `pending_registration`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_otp` (`otp_code`);

--
-- Indexes for table `transport`
--
ALTER TABLE `transport`
  ADD PRIMARY KEY (`transport_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`User_id`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- Indexes for table `user_block`
--
ALTER TABLE `user_block`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `warehouse`
--
ALTER TABLE `warehouse`
  ADD PRIMARY KEY (`warehouse_id`);

--
-- Indexes for table `warehouse_stock`
--
ALTER TABLE `warehouse_stock`
  ADD PRIMARY KEY (`ws_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_log`
--
ALTER TABLE `ai_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `customer_cart`
--
ALTER TABLE `customer_cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT for table `customer_order`
--
ALTER TABLE `customer_order`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `farm_inventory`
--
ALTER TABLE `farm_inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `farm_product`
--
ALTER TABLE `farm_product`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `government_prices`
--
ALTER TABLE `government_prices`
  MODIFY `price_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `land`
--
ALTER TABLE `land`
  MODIFY `land_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `otp_verification`
--
ALTER TABLE `otp_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `pending_registration`
--
ALTER TABLE `pending_registration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `transport`
--
ALTER TABLE `transport`
  MODIFY `transport_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `User_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10000006;

--
-- AUTO_INCREMENT for table `warehouse`
--
ALTER TABLE `warehouse`
  MODIFY `warehouse_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `warehouse_stock`
--
ALTER TABLE `warehouse_stock`
  MODIFY `ws_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `fk_admin_user` FOREIGN KEY (`admin_id`) REFERENCES `user` (`User_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `customer`
--
ALTER TABLE `customer`
  ADD CONSTRAINT `fk_customer_user` FOREIGN KEY (`customer_id`) REFERENCES `user` (`User_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `customer_cart`
--
ALTER TABLE `customer_cart`
  ADD CONSTRAINT `fk_cart_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `farm_product` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `customer_order`
--
ALTER TABLE `customer_order`
  ADD CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `farmer`
--
ALTER TABLE `farmer`
  ADD CONSTRAINT `fk_farmer_user` FOREIGN KEY (`farmer_id`) REFERENCES `user` (`User_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `farm_inventory`
--
ALTER TABLE `farm_inventory`
  ADD CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `farm_product` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `farm_product`
--
ALTER TABLE `farm_product`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_product_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `farmer` (`farmer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `land`
--
ALTER TABLE `land`
  ADD CONSTRAINT `fk_land_farmer` FOREIGN KEY (`farmer_id`) REFERENCES `farmer` (`farmer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_orderitem_order` FOREIGN KEY (`order_id`) REFERENCES `customer_order` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_orderitem_product` FOREIGN KEY (`product_id`) REFERENCES `farm_product` (`product_id`) ON UPDATE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `customer_order` (`order_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
