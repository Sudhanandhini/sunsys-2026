-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 02, 2026 at 01:17 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sunsys_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_login_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `name`, `email`, `password_hash`, `created_at`, `last_login_at`) VALUES
(1, 'Sudha Admin', 'admin@sunsys.test', '$2y$10$.R6w/.03A6HW4u4PqjXsG.KrieeTtWIUbUn0ojnuSrlELM3685FQ2', '2026-10-20 15:43:43', '2026-10-22 10:55:04');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(10) UNSIGNED NOT NULL,
  `job_id` int(10) UNSIGNED DEFAULT NULL,
  `job_title` varchar(160) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `phone` varchar(30) NOT NULL DEFAULT '',
  `experience` varchar(60) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `cv_file` varchar(120) DEFAULT NULL,
  `cv_name` varchar(200) DEFAULT NULL,
  `status` enum('new','reviewed','shortlisted','rejected') NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `job_id`, `job_title`, `name`, `email`, `phone`, `experience`, `message`, `cv_file`, `cv_name`, `status`, `created_at`) VALUES
(3, 2, 'WordPress / PHP Developer', 'Sudhanandhini Subramanian', 'sudhanandhinis@gmail.com', '+919360798606', '3 years to 5 Years', '', '20261020-e4d7689351af656af598.pdf', '35.pdf', 'shortlisted', '2026-10-20 16:13:22');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(90) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `sort_order`) VALUES
(1, 'Website guides', 'website-guides', 0),
(2, 'E-commerce', 'e-commerce', 1),
(3, 'Hosting & care', 'hosting-care', 2);

-- --------------------------------------------------------

--
-- Table structure for table `enquiries`
--

CREATE TABLE `enquiries` (
  `id` int(10) UNSIGNED NOT NULL,
  `source` varchar(40) NOT NULL DEFAULT 'contact',
  `name` varchar(120) NOT NULL DEFAULT '',
  `email` varchar(190) NOT NULL DEFAULT '',
  `phone` varchar(30) NOT NULL DEFAULT '',
  `subject` varchar(200) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `status` enum('new','read','replied','archived') NOT NULL DEFAULT 'new',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enquiries`
--

INSERT INTO `enquiries` (`id`, `source`, `name`, `email`, `phone`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 'contact', 'Ravi', 'ravi@example.com', '9876543210', 'Web Hosting', 'Need hosting for 3 sites', 'read', '2026-10-20 15:43:44'),
(4, 'contact', 'test', 'test@gmail.com', '9874563215', 'WordPress Development', 'test hdjtj', 'read', '2026-10-21 10:52:41'),
(5, 'quote', 'Sudhanandhini Subramanian', 'sudhanandhinis@gmail.com', '+919360798606', 'tsstryt', 'tsstryt', 'new', '2026-10-21 10:53:28');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(160) NOT NULL,
  `type` varchar(60) NOT NULL DEFAULT 'Full-time',
  `experience` varchar(60) NOT NULL DEFAULT '',
  `location` varchar(120) NOT NULL DEFAULT 'Bengaluru',
  `summary` varchar(400) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `title`, `type`, `experience`, `location`, `summary`, `description`, `status`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'React Developer', 'Full-time', '1 to 3 years', 'Bengaluru', 'Build React and Node.js applications for client projects.', 'Build React and Node.js applications for client projects.', 'closed', 0, '2026-10-20 15:43:43', '2026-10-20 16:14:44'),
(2, 'WordPress / PHP Developer', 'Full-time', '1 to 3 years', 'Bengaluru', 'Develop custom themes and plugins and maintain client sites.', 'Develop custom themes and plugins and maintain client sites.', 'open', 1, '2026-10-20 15:43:43', '2026-10-20 15:43:43'),
(3, 'Digital Marketing Executive', 'Full-time', '0 to 2 years', 'Bengaluru', 'Run SEO and social media campaigns for our clients.', 'Run SEO and social media campaigns for our clients.', 'open', 2, '2026-10-20 15:43:43', '2026-10-20 15:43:43');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(140) NOT NULL,
  `title` varchar(200) NOT NULL,
  `excerpt` varchar(400) NOT NULL DEFAULT '',
  `body` mediumtext NOT NULL,
  `blocks` mediumtext DEFAULT NULL,
  `meta_title` varchar(200) NOT NULL DEFAULT '',
  `meta_description` varchar(400) NOT NULL DEFAULT '',
  `noindex` tinyint(1) NOT NULL DEFAULT 0,
  `nofollow` tinyint(1) NOT NULL DEFAULT 0,
  `cover` varchar(255) DEFAULT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `slug`, `title`, `excerpt`, `body`, `blocks`, `meta_title`, `meta_description`, `noindex`, `nofollow`, `cover`, `category_id`, `status`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 'how-long-does-it-take-to-build-a-business-website', 'How long does it take to build a business website?', 'A realistic timeline for a 10 to 15 page site, and what speeds it up or slows it down.', 'Knowing your Niche\n\nA niche is a small group of people with a shared interest in something specific. The more focused your niche, the better you can serve them and the more money you will make. Many E-commerce website development companies recommend this point. Knowing your niche is the first step toward starting an eCommerce business.\n\nIf you want to start your own eCommerce business, you need to know about the product, market, and competitors in detail. Knowing your niche helps you to focus on a particular segment of the market. It also helps you to identify your target audience, who are more likely to buy from you because they are interested in products similar to yours.\n\nEg: If you sell jewelry, your niche might be “women between 20 and 30 who like to shop online.” If you sell diet supplements, your niche might be “men who want to lose weight but hate exercise.”It is important for every business owner that knows about his competitors as well so that he can compete with them effectively and win over them by providing better quality services or products at cheaper prices or faster delivery times etc.\n\nUnderstanding your Customers\n\nUnderstanding your customers is important in any business, but it’s especially crucial if you’re selling products and services online. Ecommerce businesses are highly dependent on their ability to attract and retain customers, so knowing what makes them tick is key. How can you do this? Here are some of the best ways: Understand their demographics: Demographics are simply the characteristics of a population, such as age, gender, income level, and education level. Knowing more about who’s buying from you can help guide decisions about things like pricing (if female millennials love your products but middle-aged men don’t buy them), product design (if older women aren’t into digital devices), marketing campaigns (if millennials love referral programs) or even hiring practices (if most employees are under 30) You may also want to look at how these demographics have been behaving lately—for example: Are they moving into larger homes? Do they prefer organic foods over processed ones? The answers could help determine which markets will be most profitable for your business model going forward! Understand their behavior patterns: across different channels – When someone visits an eCommerce website there’s a good chance they’re looking for something specific like clothing sizes or shoe sizes that match their body type so we make sure all our inventory has those options available because otherwise, we would lose out on sales opportunities. This means focusing on improving customer service through both front-end user experience improvements such as better search functionality or back-end order processing improvements such as faster shipping speeds in order to maximize customer satisfaction levels in both cases since happier customers tend to spend more money over time.\n\nWhat Kind of Product do you Sell?\n\nMany eCommerce companies recommend using this idea. You need to understand the product you want to sell. You need to understand the market you are selling to. You should also know your competition, who they are, what they do and how they work. What is their pricing like?\n\nYou should research thoroughly the supply chain and distribution of your products.\n\nResearching thoroughly will help you in understanding how things move along in the supply chain and where exactly your product will come from when it reaches its destination, whether that’s a warehouse or a customer’s house.\n\nLast but not least, don’t forget about marketing! Marketing is important for any business whether online or offline because without marketing no one knows about your business so make sure that you have good advertising strategies in place before starting out with eCommerce ventures.\n\nHave a Plan B\n\nHaving a Plan B is important because, while you want your business to succeed, it is not going to be the only thing you do in life. You are going to have other things going on that might take your attention away from the business.One more reason why having a backup plan is important: sometimes things don’t go according to plan! It’s often possible for entrepreneurs who have done their research and planned carefully still face problems along their way.Unexpected expenses can arise, customers may react differently than expected, government policies may change, competitors might enter the market unexpectedly… You name it! The list goes on forever! This is why having contingency plans ready beforehand will help prevent major losses while making sure there’s always something left over if something goes wrong with one part of our strategy. Many web design company in Bangalore have used this shortcut to success in previous years.\n\nBe Aware of the Competitors\n\nWhile you are determining the feasibility of your eCommerce business, it’s important that you also take a look at your competitors. If there are no competitors in the industry, then this is not a good sign for an eCommerce business.\n\nYou should do some research on them and understand their strengths and weaknesses. Know more about their price points, marketing strategies, and customer service.\n\nConclusion We hope these tips have given you some insight into the world of e-commerce and how to make it work for you. It’s important to remember that e-commerce is not a one-size-fits-all solution for your business needs.\n\nEvery business owner has different goals and priorities, so take the time to evaluate what those are before committing yourself or your company to any particular course of action. know about these before starting your ecommerce store.\n\nPros:\n\nLots of features\n\nProduct variants\n\nHigher scalability\n\nCons:\n\nLack of marketing tools\n\nYearly sales threshold\n\nFeature\n\nRegular Taq\n\nHotStart Taq\n\nActivity at room temperature\n\nLow but present\n\nBlocked until heat activation\n\nPrimer-dimers / non-specific products\n\nMore likely\n\nSignificantly reduced\n\nSensitivity with low-copy template\n\nModerate\n\nHigher\n\nMultiplex PCR\n\nChallenging\n\nBetter suited\n\nReaction setup flexibility\n\nIce recommended\n\nRoom-temperature setup possible\n\nInitial activation step\n\nNot needed\n\nRequired (check datasheet)\n\nCost\n\nLower\n\nHigher\n\nFeature\nRegular Taq\nHotStart Taq\nActivity at room temperature\nLow but present\nBlocked until heat activation\nPrimer-dimers / non-specific products\nMore likely\nSignificantly reduced\nSensitivity with low-copy template\nModerate\nHigher\nMultiplex PCR\nChallenging\nBetter suited\nReaction setup flexibility\nIce recommended\nRoom-temperature setup possible\nInitial activation step\nNot needed\nRequired (check datasheet)\nCost\nLower\nHigher\nRegular Taq\nHotStart Taq\nActivity at room temperature\nLow but present\nBlocked until heat activation\nPrimer-dimers / non-specific products\nMore likely\nSignificantly reduced\nSensitivity with low-copy template\nModerate\nHigher\nMultiplex PCR\nChallenging\nBetter suited\nReaction setup flexibility\nIce recommended\nRoom-temperature setup possible\nInitial activation step\nNot needed\nRequired (check datasheet)\nCost\nLower\nHigher\n\n642684858 54962d43 8af8 4568 96fb 57483416ffa1 Welcome', '{\"v\":1,\"rows\":[{\"cols\":[{\"widgets\":[{\"type\":\"list\",\"ordered\":true,\"items\":[\"Knowing your Niche\"]},{\"type\":\"paragraph\",\"html\":\"<p>A niche is a small group of people with a shared interest in something specific. The more focused your niche, the better you can serve them and the more money you will make. Many E-commerce website development companies recommend this point. Knowing your niche is the first step toward starting an eCommerce business.</p>\"},{\"type\":\"paragraph\",\"html\":\"<p>If you want to start your own eCommerce business, you need to know about the product, market, and competitors in detail. Knowing your niche helps you to focus on a particular segment of the market. It also helps you to identify your target audience, who are more likely to buy from you because they are interested in products similar to yours.</p>\"},{\"type\":\"paragraph\",\"html\":\"<p>Eg: If you sell jewelry, your niche might be “women between 20 and 30 who like to shop online.” If you sell diet supplements, your niche might be “men who want to lose weight but hate exercise.”It is important for every business owner that knows about his competitors as well so that he can compete with them effectively and win over them by providing better quality services or products at cheaper prices or faster delivery times etc.</p>\"},{\"type\":\"list\",\"ordered\":true,\"items\":[\"Understanding your Customers\"]},{\"type\":\"paragraph\",\"html\":\"<p>Understanding your customers is important in any business, but it’s especially crucial if you’re selling products and services online. Ecommerce businesses are highly dependent on their ability to attract and retain customers, so knowing what makes them tick is key. How can you do this? Here are some of the best ways: Understand their demographics: Demographics are simply the characteristics of a population, such as age, gender, income level, and education level. Knowing more about who’s buying from you can help guide decisions about things like pricing (if female millennials love your products but middle-aged men don’t buy them), product design (if older women aren’t into digital devices), marketing campaigns (if millennials love referral programs) or even hiring practices (if most employees are under 30) You may also want to look at how these demographics have been behaving lately—for example: Are they moving into larger homes? Do they prefer organic foods over processed ones? The answers could help determine which markets will be most profitable for your business model going forward! Understand their behavior patterns: across different channels – When someone visits an eCommerce website there’s a good chance they’re looking for something specific like clothing sizes or shoe sizes that match their body type so we make sure all our inventory has those options available because otherwise, we would lose out on sales opportunities. This means focusing on improving customer service through both front-end user experience improvements such as better search functionality or back-end order processing improvements such as faster shipping speeds in order to maximize customer satisfaction levels in both cases since happier customers tend to spend more money over time.</p>\"},{\"type\":\"list\",\"ordered\":true,\"items\":[\"What Kind of Product do you Sell?\"]},{\"type\":\"paragraph\",\"html\":\"<p>Many eCommerce companies recommend using this idea. You need to understand the product you want to sell. You need to understand the market you are selling to. You should also know your competition, who they are, what they do and how they work. What is their pricing like?</p>\"},{\"type\":\"paragraph\",\"html\":\"<p>You should research thoroughly the supply chain and distribution of your products.</p>\"},{\"type\":\"paragraph\",\"html\":\"<p>Researching thoroughly will help you in understanding how things move along in the supply chain and where exactly your product will come from when it reaches its destination, whether that’s a warehouse or a customer’s house.</p>\"},{\"type\":\"paragraph\",\"html\":\"<p>Last but not least, don’t forget about marketing! Marketing is important for any business whether online or offline because without marketing no one knows about your business so make sure that you have good advertising strategies in place before starting out with eCommerce ventures.</p>\"},{\"type\":\"list\",\"ordered\":true,\"items\":[\"Have a Plan B\"]},{\"type\":\"paragraph\",\"html\":\"<p>Having a Plan B is important because, while you want your business to succeed, it is not going to be the only thing you do in life. You are going to have other things going on that might take your attention away from the business.One more reason why having a backup plan is important: sometimes things don’t go according to plan! It’s often possible for entrepreneurs who have done their research and planned carefully still face problems along their way.Unexpected expenses can arise, customers may react differently than expected, government policies may change, competitors might enter the market unexpectedly… You name it! The list goes on forever! This is why having contingency plans ready beforehand will help prevent major losses while making sure there’s always something left over if something goes wrong with one part of our strategy. Many web design company in Bangalore have used this shortcut to success in previous years.</p>\"},{\"type\":\"list\",\"ordered\":true,\"items\":[\"Be Aware of the Competitors\"]},{\"type\":\"paragraph\",\"html\":\"<p>While you are determining the feasibility of your eCommerce business, it’s important that you also take a look at your competitors. If there are no competitors in the industry, then this is not a good sign for an eCommerce business.</p>\"},{\"type\":\"paragraph\",\"html\":\"<p>You should do some research on them and understand their strengths and weaknesses. Know more about their price points, marketing strategies, and customer service.</p>\"},{\"type\":\"paragraph\",\"html\":\"<p>Conclusion We hope these tips have given you some insight into the world of e-commerce and how to make it work for you. It’s important to remember that e-commerce is not a one-size-fits-all solution for your business needs.</p>\"},{\"type\":\"paragraph\",\"html\":\"<p>Every business owner has different goals and priorities, so take the time to evaluate what those are before committing yourself or your company to any particular course of action. know about these before starting your ecommerce store.</p>\"},{\"type\":\"paragraph\",\"html\":\"<p>Pros:</p><p>Lots of features</p><p>Product variants</p><p>Higher scalability</p><p>Cons:</p><p>Lack of marketing tools</p><p>Yearly sales threshold</p>\"}]}]},{\"cols\":[{\"widgets\":[{\"type\":\"paragraph\",\"html\":\"<p>Feature</p><p>Regular Taq</p><p>HotStart Taq</p><p>Activity at room temperature</p><p>Low but present</p><p>Blocked until heat activation</p><p>Primer-dimers / non-specific products</p><p>More likely</p><p>Significantly reduced</p><p>Sensitivity with low-copy template</p><p>Moderate</p><p>Higher</p><p>Multiplex PCR</p><p>Challenging</p><p>Better suited</p><p>Reaction setup flexibility</p><p>Ice recommended</p><p>Room-temperature setup possible</p><p>Initial activation step</p><p>Not needed</p><p>Required (check datasheet)</p><p>Cost</p><p>Lower</p><p>Higher</p>\"},{\"type\":\"list\",\"ordered\":false,\"items\":[\"Feature<br>Regular Taq<br>HotStart Taq<br>Activity at room temperature<br>Low but present<br>Blocked until heat activation<br>Primer-dimers / non-specific products<br>More likely<br>Significantly reduced<br>Sensitivity with low-copy template<br>Moderate<br>Higher<br>Multiplex PCR<br>Challenging<br>Better suited<br>Reaction setup flexibility<br>Ice recommended<br>Room-temperature setup possible<br>Initial activation step<br>Not needed<br>Required (check datasheet)<br>Cost<br>Lower<br>Higher\",\"Regular Taq\",\"HotStart Taq\",\"Activity at room temperature\",\"Low but present\",\"Blocked until heat activation\",\"Primer-dimers / non-specific products\",\"More likely\",\"Significantly reduced\",\"Sensitivity with low-copy template\",\"Moderate\",\"Higher\",\"Multiplex PCR\",\"Challenging\",\"Better suited\",\"Reaction setup flexibility\",\"Ice recommended\",\"Room-temperature setup possible\",\"Initial activation step\",\"Not needed\",\"Required (check datasheet)\",\"Cost\",\"Lower\",\"Higher\"]},{\"type\":\"image\",\"src\":\"/uploads/blog/202610-ffac3415459a95f7.jpg\",\"alt\":\"642684858 54962d43 8af8 4568 96fb 57483416ffa1\",\"caption\":\"Welcome\",\"href\":\"\"}]}]}]}', '', '', 0, 0, '/uploads/blog/202610-ab1ae75e09e201b6.jpg', 1, 'published', '2026-10-20 15:56:00', '2026-10-20 15:43:43', '2026-10-21 11:10:01'),
(2, 'wordpress-or-custom-build-which-fits-your-business', 'WordPress or custom build: which fits your business?', 'When a CMS is the right call, and when a custom application saves money long term.', '1. Knowing your Niche\r\nA niche is a small group of people with a shared interest in something specific. The more focused your niche, the better you can serve them and the more money you will make. Many E-commerce website development companies recommend this point. Knowing your niche is the first step toward starting an eCommerce business. \r\n\r\nIf you want to start your own eCommerce business, you need to know about the product, market, and competitors in detail. Knowing your niche helps you to focus on a particular segment of the market. It also helps you to identify your target audience, who are more likely to buy from you because they are interested in products similar to yours.\r\n\r\nEg: If you sell jewelry, your niche might be “women between 20 and 30 who like to shop online.” If you sell diet supplements, your niche might be “men who want to lose weight but hate exercise.”It is important for every business owner that knows about his competitors as well so that he can compete with them effectively and win over them by providing better quality services or products at cheaper prices or faster delivery times etc.\r\n\r\n2. Understanding your Customers\r\nUnderstanding your customers is important in any business, but it’s especially crucial if you’re selling products and services online. Ecommerce businesses are highly dependent on their ability to attract and retain customers, so knowing what makes them tick is key. How can you do this? Here are some of the best ways:\r\nUnderstand their demographics: Demographics are simply the characteristics of a population, such as age, gender, income level, and education level. Knowing more about who’s buying from you can help guide decisions about things like pricing (if female millennials love your products but middle-aged men don’t buy them), product design (if older women aren’t into digital devices), marketing campaigns (if millennials love referral programs) or even hiring practices (if most employees are under 30)\r\nYou may also want to look at how these demographics have been behaving lately—for example: Are they moving into larger homes? Do they prefer organic foods over processed ones? The answers could help determine which markets will be most profitable for your business model going forward!\r\nUnderstand their behavior patterns: across different channels – When someone visits an eCommerce website there’s a good chance they’re looking for something specific like clothing sizes or shoe sizes that match their body type so we make sure all our inventory has those options available because otherwise, we would lose out on sales opportunities.\r\nThis means focusing on improving customer service through both front-end user experience improvements such as better search functionality or back-end order processing improvements such as faster shipping speeds in order to maximize customer satisfaction levels in both cases since happier customers tend to spend more money over time.\r\n3. What Kind of Product do you Sell?\r\nMany eCommerce companies recommend using this idea. You need to understand the product you want to sell. You need to understand the market you are selling to. You should also know your competition, who they are, what they do and how they work. What is their pricing like?\r\n\r\nYou should research thoroughly the supply chain and distribution of your products.\r\n\r\nResearching thoroughly will help you in understanding how things move along in the supply chain and where exactly your product will come from when it reaches its destination, whether that’s a warehouse or a customer’s house.\r\n\r\nLast but not least, don’t forget about marketing! Marketing is important for any business whether online or offline because without marketing no one knows about your business so make sure that you have good advertising strategies in place before starting out with eCommerce ventures.\r\n\r\n4. Have a Plan B\r\nHaving a Plan B is important because, while you want your business to succeed, it is not going to be the only thing you do in life. You are going to have other things going on that might take your attention away from the business.One more reason why having a backup plan is important: sometimes things don’t go according to plan! It’s often possible for entrepreneurs who have done their research and planned carefully still face problems along their way.Unexpected expenses can arise, customers may react differently than expected, government policies may change, competitors might enter the market unexpectedly… You name it! The list goes on forever! This is why having contingency plans ready beforehand will help prevent major losses while making sure there’s always something left over if something goes wrong with one part of our strategy. Many web design company in Bangalore have used this shortcut to success in previous years.\r\n\r\n5. Be Aware of the Competitors\r\nWhile you are determining the feasibility of your eCommerce business, it’s important that you also take a look at your competitors. If there are no competitors in the industry, then this is not a good sign for an eCommerce business.\r\n\r\nYou should do some research on them and understand their strengths and weaknesses. Know more about their price points, marketing strategies, and customer service.\r\n\r\nConclusion\r\nWe hope these tips have given you some insight into the world of e-commerce and how to make it work for you. It’s important to remember that e-commerce is not a one-size-fits-all solution for your business needs.\r\n\r\nEvery business owner has different goals and priorities, so take the time to evaluate what those are before committing yourself or your company to any particular course of action. know about these before starting your ecommerce store.', NULL, '', '', 0, 0, '/uploads/blog/202610-842ededaa8ac5c07.jpg', 1, 'published', '2026-10-20 15:56:00', '2026-10-20 15:43:43', '2026-10-20 16:10:51'),
(4, 'five-checks-before-you-launch', '5 Things You Need to Know Before Starting an eCommerce Business', 'Why it matters A launch checklist saves time and avoids surprises on day one. Test every form Check SSL Measure twice, launch once. The checklist Go through each item with your team before the site g…', '1. Knowing your Niche\r\nA niche is a small group of people with a shared interest in something specific. The more focused your niche, the better you can serve them and the more money you will make. Many E-commerce website development companies recommend this point. Knowing your niche is the first step toward starting an eCommerce business. \r\n\r\nIf you want to start your own eCommerce business, you need to know about the product, market, and competitors in detail. Knowing your niche helps you to focus on a particular segment of the market. It also helps you to identify your target audience, who are more likely to buy from you because they are interested in products similar to yours.\r\n\r\nEg: If you sell jewelry, your niche might be “women between 20 and 30 who like to shop online.” If you sell diet supplements, your niche might be “men who want to lose weight but hate exercise.”It is important for every business owner that knows about his competitors as well so that he can compete with them effectively and win over them by providing better quality services or products at cheaper prices or faster delivery times etc.\r\n\r\n2. Understanding your Customers\r\nUnderstanding your customers is important in any business, but it’s especially crucial if you’re selling products and services online. Ecommerce businesses are highly dependent on their ability to attract and retain customers, so knowing what makes them tick is key. How can you do this? Here are some of the best ways:\r\nUnderstand their demographics: Demographics are simply the characteristics of a population, such as age, gender, income level, and education level. Knowing more about who’s buying from you can help guide decisions about things like pricing (if female millennials love your products but middle-aged men don’t buy them), product design (if older women aren’t into digital devices), marketing campaigns (if millennials love referral programs) or even hiring practices (if most employees are under 30)\r\nYou may also want to look at how these demographics have been behaving lately—for example: Are they moving into larger homes? Do they prefer organic foods over processed ones? The answers could help determine which markets will be most profitable for your business model going forward!\r\nUnderstand their behavior patterns: across different channels – When someone visits an eCommerce website there’s a good chance they’re looking for something specific like clothing sizes or shoe sizes that match their body type so we make sure all our inventory has those options available because otherwise, we would lose out on sales opportunities.\r\nThis means focusing on improving customer service through both front-end user experience improvements such as better search functionality or back-end order processing improvements such as faster shipping speeds in order to maximize customer satisfaction levels in both cases since happier customers tend to spend more money over time.\r\n3. What Kind of Product do you Sell?\r\nMany eCommerce companies recommend using this idea. You need to understand the product you want to sell. You need to understand the market you are selling to. You should also know your competition, who they are, what they do and how they work. What is their pricing like?\r\n\r\nYou should research thoroughly the supply chain and distribution of your products.\r\n\r\nResearching thoroughly will help you in understanding how things move along in the supply chain and where exactly your product will come from when it reaches its destination, whether that’s a warehouse or a customer’s house.\r\n\r\nLast but not least, don’t forget about marketing! Marketing is important for any business whether online or offline because without marketing no one knows about your business so make sure that you have good advertising strategies in place before starting out with eCommerce ventures.\r\n\r\n4. Have a Plan B\r\nHaving a Plan B is important because, while you want your business to succeed, it is not going to be the only thing you do in life. You are going to have other things going on that might take your attention away from the business.One more reason why having a backup plan is important: sometimes things don’t go according to plan! It’s often possible for entrepreneurs who have done their research and planned carefully still face problems along their way.Unexpected expenses can arise, customers may react differently than expected, government policies may change, competitors might enter the market unexpectedly… You name it! The list goes on forever! This is why having contingency plans ready beforehand will help prevent major losses while making sure there’s always something left over if something goes wrong with one part of our strategy. Many web design company in Bangalore have used this shortcut to success in previous years.\r\n\r\n5. Be Aware of the Competitors\r\nWhile you are determining the feasibility of your eCommerce business, it’s important that you also take a look at your competitors. If there are no competitors in the industry, then this is not a good sign for an eCommerce business.\r\n\r\nYou should do some research on them and understand their strengths and weaknesses. Know more about their price points, marketing strategies, and customer service.\r\n\r\nConclusion\r\nWe hope these tips have given you some insight into the world of e-commerce and how to make it work for you. It’s important to remember that e-commerce is not a one-size-fits-all solution for your business needs.\r\n\r\nEvery business owner has different goals and priorities, so take the time to evaluate what those are before committing yourself or your company to any particular course of action. know about these before starting your ecommerce store.', NULL, '', '', 0, 0, '/uploads/blog/202610-f5ba30043c73d001.jpg', 2, 'published', '2026-10-20 15:44:00', '2026-10-20 15:44:18', '2026-10-20 16:10:51');

-- --------------------------------------------------------

--
-- Table structure for table `rate_limits`
--

CREATE TABLE `rate_limits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bucket` varchar(30) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seo_pages`
--

CREATE TABLE `seo_pages` (
  `id` int(10) UNSIGNED NOT NULL,
  `path` varchar(255) NOT NULL,
  `name` varchar(120) NOT NULL DEFAULT '',
  `type` varchar(30) NOT NULL DEFAULT 'Page',
  `custom` tinyint(1) NOT NULL DEFAULT 0,
  `title` varchar(200) NOT NULL DEFAULT '',
  `description` varchar(400) NOT NULL DEFAULT '',
  `keywords` varchar(300) NOT NULL DEFAULT '',
  `canonical` varchar(500) NOT NULL DEFAULT '',
  `og_title` varchar(200) NOT NULL DEFAULT '',
  `og_description` varchar(400) NOT NULL DEFAULT '',
  `og_image` varchar(500) NOT NULL DEFAULT '',
  `schema_json` mediumtext DEFAULT NULL,
  `noindex` tinyint(1) NOT NULL DEFAULT 0,
  `nofollow` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seo_pages`
--

INSERT INTO `seo_pages` (`id`, `path`, `name`, `type`, `custom`, `title`, `description`, `keywords`, `canonical`, `og_title`, `og_description`, `og_image`, `schema_json`, `noindex`, `nofollow`, `updated_at`) VALUES
(54, '/career/', 'Career', 'Page', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:15'),
(55, '/', '', 'Home', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(56, '/about/', '', 'Page', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(57, '/blog/', '', 'Blog', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(59, '/contact/', '', 'Page', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(60, '/industries/corporate/', '', 'Industry', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(61, '/industries/education/', '', 'Industry', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(62, '/industries/healthcare/', '', 'Industry', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(63, '/industries/hotel-and-resort/', '', 'Industry', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(64, '/industries/real-estate-and-construction/', '', 'Industry', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(65, '/industries/tours-and-travels/', '', 'Industry', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(66, '/services/', '', 'Page', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(67, '/services/cms-web-development/', '', 'Service', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(68, '/services/custom-web-development/', '', 'Service', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(69, '/services/ecommerce-development/', '', 'Service', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(70, '/services/email-hosting/', '', 'Service', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(71, '/services/landing-page/', '', 'Service', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(72, '/services/linux-hosting/', '', 'Service', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(73, '/services/responsive-web-design/', '', 'Service', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(74, '/services/web-hosting/', '', 'Service', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(75, '/services/website-development/', '', 'Service', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(76, '/services/website-maintenance/', '', 'Service', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(77, '/services/website-redesign/', '', 'Service', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(78, '/services/woocommerce-development/', '', 'Service', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(79, '/services/wordpress-development/', '', 'Service', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-21 11:19:52'),
(105, '/sitemap/', '', 'Page', 0, '', '', '', '', '', '', '', NULL, 1, 1, '2026-10-22 11:00:41');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `k` varchar(60) NOT NULL,
  `v` mediumtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `enquiries`
--
ALTER TABLE `enquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`),
  ADD KEY `source` (`source`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `status` (`status`,`published_at`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `rate_limits`
--
ALTER TABLE `rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bucket` (`bucket`,`ip`,`created_at`);

--
-- Indexes for table `seo_pages`
--
ALTER TABLE `seo_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `path` (`path`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`k`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `enquiries`
--
ALTER TABLE `enquiries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rate_limits`
--
ALTER TABLE `rate_limits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `seo_pages`
--
ALTER TABLE `seo_pages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
