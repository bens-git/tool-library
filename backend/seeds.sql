-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jul 13, 2024 at 01:49 AM
-- Server version: 8.0.37-0ubuntu0.20.04.3
-- PHP Version: 7.4.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tool-library`
--



--
-- Dumping data for table `categories`
--
ALTER TABLE  `categories` AUTO_INCREMENT = 0;

INSERT INTO `categories` (`name`) VALUES
('Hand tools'),
('Ryobi 18V'),
('Ryobi 40V'),
('Gas Tools'),
('Corded'),
('Large/Heavy Equipment'),
('Speciality'),
('Air powered'),
('Event'),
('PPE'),
('Other');





--
-- Dumping data for table `locations`
--
ALTER TABLE  `locations` AUTO_INCREMENT = 0;

INSERT INTO `locations` (`street_address`, `city`, `state`, `country`) VALUES
('247 Marlborough Street', 'Brantford', 'Ontario', 'Canada');




--
-- Dumping data for table `models`
--
ALTER TABLE  `types` AUTO_INCREMENT = 0;

INSERT INTO `types`(`name`,`description`,`code`) VALUES

("A frame wooden signs", "", ""),
("AirStrike Stapler", "AirStrike Stapler: Convenient cordless stapler for various fastening applications.", ""),
("Angle Grinder", "Angle Grinder: Powerful tool for cutting, grinding, and polishing metal, stone, or concrete.", "PBLAG01K1"),
("Angle Grinder - Corded", "Angle Grinder: Powerful tool for cutting, grinding, and polishing metal, stone, or concrete. 110V powered", "PBLAG01K1"),
("Tractor Backhoe", "Backhoe: A powerful excavation machine used for digging, lifting, and moving soil or heavy materials with its hydraulic arm and bucket.", ""),
("Band saw", "Band Saw - Precision cutting tool for making curved and straight cuts in different materials.", ""),
("Battery 18V 2AH ", "18V 2AH Battery - Rechargeable battery pack for powering compatible tools and devices.", ""),
("Battery 18V 3AH ", "18V 3AH Battery - High-capacity rechargeable battery for extended use of compatible tools.", ""),
("Battery Charger", "Battery Charger: Charges and rejuvenates rechargeable batteries, ensuring they're ready for use when needed.", ""),
("Belt Sander", "Belt Sander: Used for smoothing and shaping wood surfaces with its continuous sanding belt.", ""),
("Belt Sander", "Belt Sander: Used for smoothing and finishing larger wood surfaces with a rotating sanding drum.", ""),
("Bench Grinder", "Bench Grinder: Essential for sharpening, shaping, and grinding various tools and metal objects.", ""),
("Biscuit Joiner", "Biscuit Joiner: Helps in creating strong and precise joints in woodworking projects using biscuits.", ""),
("Bluetooth Speaker", "Bluetooth Speaker: Portable speaker with Bluetooth connectivity for wireless audio streaming.", ""),
("Brad Nailer", "Brad Nailer: Handy tool for precision nailing of small brad nails in woodworking projects.", ""),
("Caulking Gun", "Caulking Gun: Used for precise application of caulk or sealant for sealing gaps and joints.", ""),
("cement mixing drill extension", "", ""),
("chain with hooks", "", ""),
("Chainsaw", "Chainsaw: Efficient tool for cutting through branches, logs, and trees with ease.", ""),
("Chainsaw", "Ryobi Chainsaw: Electric commonly used for cutting through small limbs, particularly good to cut limbs at height", "RY40507BTL"),
("Chainsaw", "Chainsaw: Efficient tool for cutting through branches, logs, and trees with ease.", ""),
("Chainsaw mill", "Chainsaw Mill: A specialized attachment or standalone device used with a chainsaw to convert logs into lumber. It allows for on-site milling, making it convenient for woodworking projects and maximizing the use of available timber.", ""),
("Chop Saw", "Chop Saw: Also known as a miter saw, it's a powerful tool for making accurate crosscuts in wood, metal, or plastic.", ""),
("chop saw", "", ""),
("Chopping axe", "Chopping Axe: A versatile axe with a broad blade for cutting or splitting wood.", ""),
("Circular Saw", "Circular Saw: Essential tool for making precise and accurate cuts in various materials.", ""),
("Claw hammer", "Claw Hammer: A traditional tool with a curved claw for driving and removing nails, as well as general carpentry work.", ""),
("Come along ", "Come Along: A hand-operated winching device used for pulling, stretching, or securing heavy loads.", ""),
("copper plumbing kit", "Copper Plumbing Kit: A comprehensive package containing essential components for copper pipe plumbing installations. It typically includes copper pipes, fittings, connectors, and tools required for connecting and routing water supply lines.", ""),
("Corded Circular Saw", "Corded Circular Saw - Powerful electric saw for making precise cuts in wood and other materials.", ""),
("corded dremel", "A rotary tool that operates using a power cord for continuous and reliable power supply, suitable for extended or heavy-duty use.", ""),
("cordless dremel", "A portable and battery-powered rotary tool, typically used for various DIY projects, crafts, and light-duty tasks.", "P460"),
("Cordless Drill/Driver", "Cordless Drill/Driver: Versatile power tool for drilling holes and driving screws with ease.", "PCL206K1"),
("Crow bar", "Crowbar - Versatile tool for prying, lifting, and removing materials.", ""),
("Cut off saw", "Cut Off Saw: Also known as a cutoff or abrasive saw, it's designed for cutting metal, masonry, or plastic with a high-speed abrasive disc.", ""),
("Digital Multimeter", "Digital Multimeter: Essential for measuring voltage, current, and resistance in electrical circuits.", ""),
("Dozuki saw", "Dozuki Saw: A Japanese hand saw with a thin, fine-toothed blade, perfect for precise and clean cuts in woodworking.", ""),
("drain snake (50')", "50' Drain Snake: A long, flexible metal cable with a corkscrew-like tip used for clearing clogs and blockages in drains and pipes. It can be inserted into the plumbing system, rotated, and maneuvered to remove obstructions and restore proper water flow.", ""),
("Drill press", "Drill Press: A powerful stationary tool used for drilling precise and accurate holes in various materials, offering greater control and stability compared to handheld drills.", ""),
("Enclosed Trailer 4x8 ", "Enclosed Trailer 4x8 - Secure and weather-resistant trailer for transporting equipment and materials.", ""),
("Extenstion Ladder", "Extension ladder: A ladder with adjustable length to reach higher areas or access elevated locations.", ""),
("Finish Nailer", "Finish Nailer: Powerful tool for driving larger finish nails with precision and efficiency.", ""),
("Folding ladder", "Folding ladder: A portable ladder that can be folded for easy storage and transport.", ""),
("Generator", "Generator: Provides a portable source of electrical power, useful during power outages or in remote locations.", ""),
("Genrator", "Generator - Gas powerwed 110V/220V power source for outdoor or backup power needs.", ""),
("Glue Gun", "Glue Gun: Handy tool for quick and easy application of hot glue for various crafts and repairs.", ""),
("Grease Gun", "Grease Gun: Used for lubricating machinery and equipment with grease efficiently.", ""),
("halogen flood light", "Halogen Flood Light: A bright and efficient lighting fixture that emits a wide beam of light, commonly used for illuminating large outdoor areas.", ""),
("hand axe", "Hand Axe: A small, portable axe used for various outdoor tasks like chopping wood, clearing brush, or splitting kindling.", ""),
("hearing protection", "Hearing Protection: Essential safety equipment designed to protect your ears from loud noises, such as power tools, machinery, or construction sites. Hearing protection comes in various forms, including earmuffs and earplugs, and helps prevent potential hearing damage and discomfort.", ""),
("Heat Gun", "Heat Gun: Used for applications like paint stripping, thawing frozen pipes, and shaping materials with controlled heat.", ""),
("Hedge Trimmer", "Hedge Trimmer: Designed for trimming and shaping hedges, bushes, and shrubs for a neat appearance.", ""),
("Impact Driver", "Impact Driver: High-torque tool for driving screws and fasteners effortlessly.", ""),
("Impact Wrench", "Impact Wrench: Powerful tool for loosening or tightening bolts and nuts with high torque.", "PCL220B"),
("Inflator", "Inflator: Handy for inflating tires, sports equipment, and inflatable items with ease.", "P737D"),
("Inspection Camera", "Inspection Camera: Allows for visual inspection in hard-to-reach areas with its flexible camera probe.", ""),
("Iron bar", "Iron Bar - Strong and durable bar used for various construction and demolition tasks.", ""),
("Jigsaw", "Jigsaw: Perfect for intricate and curved cuts in wood, metal, or plastic.", ""),
("Kevlar pants", "Protective pants made from Kevlar material, designed to provide resistance against cuts, abrasions, and heat, commonly used in industrial or outdoor activities.", ""),
("Laser measure", "", ""),
("Lathe", "Lathe: A versatile machine used for shaping wood or other materials by rotating it against cutting tools.", ""),
("Leaf Blower", "Leaf Blower: Helps in clearing leaves, debris, and grass clippings from your yard effortlessly.", ""),
("LED Area Light ", "LED Area Light - Energy-efficient lighting solution for illuminating large spaces.", "PCL662B"),
("LED Flashlight", "LED Flashlight - Portable and long-lasting flashlight for various applications.", "P705"),
("Loppers", "Loppers: Long-handled cutting tools with a scissor-like design, used for pruning and trimming branches or twigs with thicker diameters.", ""),
("Metal rake", "A rake with metal tines used for gathering leaves, grass, or debris from the ground or lawn.", ""),
("Miter Saw", "Miter Saw: Perfect for making angled cuts, bevel cuts, and miter cuts in various materials.", ""),
("Multi-Tool", "Multi-Tool: Versatile tool with multiple functions such as cutting, sanding, scraping, and more.", "PBLMT50K1"),
("Open trailer  10x6 ", "Open Trailer 10x6 - Spacious and versatile trailer for hauling larger items or equipment.", ""),
("paint mixing drill extension", "", ""),
("Paint Sprayer", "Paint Sprayer: Ideal for achieving smooth and even coats of paint on surfaces quickly.", ""),
("pick axe", "Pick Axe: A versatile hand tool with a pointed end for breaking hard ground or rocks and a broad blade for digging.", ""),
("Planer 13'", "Planer: Essential for smoothing and leveling wood surfaces for precise woodworking projects.", ""),
("Pocket screw jig", "Pocket Screw Jig: Enables easy drilling of pocket holes for strong and concealed joinery in woodworking projects.", ""),
("Pole Saw", "Pole Saw: Extendable saw for trimming tree branches and hard-to-reach areas.", ""),
("pole tree pruner", "Pole Tree Pruner: A specialized pruning tool with an extendable pole and a cutting blade at the end, designed for trimming tree branches in high or hard-to-reach areas.", ""),
("Portable Fan", "Portable Fan: Provides cooling and ventilation in hot environments or confined spaces.", ""),
("Portable Hot water heater", "Portable Hot Water Heater - Convenient solution for on-the-go hot water needs.", ""),
("Power Station", "Power Station: 40V 1800 Watt power station or power generator produced by Ryobi, providing portable electrical power for various devices and equipment.", "RYI1802BTVNM"),
("Random Orbit Sander", "Random Orbit Sander: Versatile tool that combines random orbital and fine finishing sanding motions for smooth results.", ""),
("Reciprocating Saw", "Reciprocating Saw: Ideal for demolition work, cutting through different materials like wood and metal.", ""),
("Right Angle Drill", "Right Angle Drill: Ideal for drilling and driving in tight spaces and corners with its compact design", ""),
("Rolling measurement wheel", "Rolling Measurement Wheel: A handheld device with a wheel and an integrated measuring mechanism used to measure distances accurately. It is commonly used in construction, landscaping, and surveying projects to quickly determine lengths, distances, or areas.", ""),
("Rotary Hammer", "Rotary Hammer: Heavy-duty tool designed for drilling and chiseling through tough materials like concrete and masonry.", "P222K1"),
("Rototiller", "Rototiller: A motorized gardening tool used for tilling and cultivating soil, preparing it for planting.", ""),
("Router", "Router: Versatile tool used for shaping, trimming, and cutting various materials, especially wood.", ""),
("Seed spreader", "Seed Spreader: A device used for evenly distributing seeds, fertilizer, or other granular materials on lawns or gardens.", ""),
("Sledge hammer", "Sledge Hammer - Heavy-duty tool for powerful impact and demolition work.", ""),
("Snow blower", "Snow Blower: An efficient machine designed to remove snow from driveways, walkways, and other surfaces. It utilizes a rotating auger or impeller to discharge snow, making snow removal faster and easier compared to traditional shoveling.", ""),
("Soil auger set (2',4',6')", "", ""),
("String Trimmer", "String Trimmer: Perfect for maintaining the edges of lawns and cutting grass in hard-to-reach areas.", ""),
("Stump remover", "Stump Remover: Helps in the removal of tree stumps by breaking them apart or extracting them from the ground.", ""),
("Table Saw", "Table Saw: Precise cutting tool with a flat table surface for accurate rip cuts and crosscuts.", ""),
("Tie Down Straps", "Tie Down Straps: Heavy-duty straps designed to secure and fasten objects during transportation or storage.", ""),
("Tie down straps - Large", "Tie Down Straps - Large: Heavy-duty straps with durable buckles or hooks designed for securing and immobilizing large or bulky items during transportation or storage. These tie-down straps provide a reliable and adjustable means to hold down and secure cargo, ensuring safe and stable transport.", ""),
("Tile Saw", "Tile Saw: Perfect for precise cutting of tiles and other materials used in tiling projects.", ""),
("traffic pylons (36' Orange )", "36' Orange Pylons: Highly visible traffic cones or markers in a vibrant orange color, measuring 36 inches in height. These pylons are commonly used to delineate or redirect traffic, mark hazardous areas, or create temporary barriers and boundaries in construction sites, events, or roadwork zones.", ""),
("trenching shovel", "Trenching Shovel: Designed with a narrow, flat blade for digging long and narrow trenches for various purposes.", ""),
("Wet/Dry Vacuum", "Wet/Dry Vacuum: Versatile vacuum cleaner capable of handling both wet and dry messes.", ""),
("Wood burner", "Wood burner: A tool equipped with a heated tip used for creating decorative patterns, designs, or burning images onto wood surfaces.", ""),
("Wood splitting axe", "Wood Splitting Axe: Designed for splitting logs or firewood, featuring a wedge-shaped blade and a long handle for leverage.", "");



-- Dumping data for table `usages`
--
ALTER TABLE  `usages` AUTO_INCREMENT = 0;

INSERT INTO `usages` (`name`) VALUES

('Basics'),
('Garden and Farm'),
('Wood Working'),
('Plumbing'),
('Electrical'),
('Masonary'),
('Tree Falling and Brush Clearing'),
('Drywall / Painting'),
('Measurement'),
('Automotive'),
('Cleaning'),
('Food Preperation'),
('Event'),
('Metal working'),
('Demolition'),
('Crafts'),
('Moving'),
('Other');




--
-- Dumping data for table `users`
--
ALTER TABLE  `users` AUTO_INCREMENT = 0;

INSERT INTO `users` (`id`, `name`, `email`, `password`,  `location_id`) VALUES
(1,'Benjamin', 'mail.ben.small@gmail.com',  '$2y$13$0L4FJA/guW1x/A9xdUsQue7raXId7uf4AAlFAzCLEtAgk1scNreN6',1);



--
-- Dumping data for table `items`
--
ALTER TABLE  `items` AUTO_INCREMENT = 0;

INSERT INTO `items` (`type_id`, `purchase_value`, `owned_by`, `location_id`)
VALUES
(1,  '50.00', 1, 1),
(1,  '50.00', 1, 1),
(1,  '50.00', 1, 1),
(1,  '50.00', 1, 1),
(1,  '50.00', 1, 1),
(1,  '50.00', 1, 1),
(1,  '50.00', 1, 1),
(1,  '50.00', 1, 1),
(1,  '50.00', 1, 1),
(1,  '50.00', 1, 1),
(1,  '50.00', 1, 1),
(1,  '50.00', 1, 1),
(2,  '50.00', 1, 1),
(3,  '90.00', 1, 1),
(4,  '90.00', 1, 1),
(5,  '15000', 1, 1),
(6,  '500.00', 1, 1),
(7,  '100.00', 1, 1),
(7,  '100.00', 1, 1),
(8,  '150.00', 1, 1),
(8,  '150.00', 1, 1),
(8,  '150.00', 1, 1),
(8,  '150.00', 1, 1),
(9,  '80.00' , 1, 1),
(10,  '90.00' , 1, 1),
(11,  '80.00', 1, 1),
(12,  '100.0', 1, 1),
(14,  '80.00', 1, 1),
(15,  '170.0', 1, 1),
(16,  '40.00', 1, 1),
(17,   '', 1, 1),
(18,   '', 1, 1),
(19,   '1100', 1, 1),
(20,   '500.0', 1, 1),
(21,   '1100', 1, 1),
(22,   '200.0', 1, 1),
(23,   '150.0', 1, 1),
(24,   '', 1, 1),
(25,  '50.00', 1, 1),
(26,  '70.00', 1, 1),
(27,  '20.00', 1, 1),
(28,  '70.00', 1, 1),
(29,  '350.0', 1, 1),
(30,  '170.0', 1, 1),
(31,  '150.0', 1, 1),
(32,  '129.0', 1, 1),
(33,  '60.00', 1, 1),
(33,  '60.00', 1, 1),
(33,  '60.00', 1, 1),
(34,  '30.00', 1, 1),
(34,  '30.00', 1, 1),
(35,  '150.0', 1, 1),
(36,  '90.00', 1, 1),
(37,  '25.00', 1, 1),
(38,  '75.00', 1, 1),
(39,  '500.0', 1, 1),
(40,  '1900', 1, 1),
(41,  '300.0', 1, 1),
(42,  '190.0', 1, 1),
(43,  '150.0', 1, 1),
(44,  '400.0', 1, 1),
(45,  '250.0', 1, 1),
(46,  '40.00', 1, 1),
(47,  '40.00', 1, 1),
(48,  '100.0', 1, 1),
(49,  '25.00', 1, 1),
(50,  '50.00', 1, 1),
(50,  '50.00', 1, 1),
(50,  '50.00', 1, 1),
(51,  '30.00', 1, 1),
(52,  '50.00', 1, 1),
(53,  '60.00', 1, 1),
(54,  '120.0', 1, 1),
(55,  '80.00', 1, 1),
(56,  '', 1, 1),
(57,  '50.00', 1, 1),
(58,  '50.00', 1, 1),
(59,  '205.0', 1, 1),
(60,   '', 1, 1),
(61,  '300.0', 1, 1),
(62,  '80.00', 1, 1),
(63,  '25.00', 1, 1),
(64,  '25.00', 1, 1),
(65,  '45.00', 1, 1),
(65,  '45.00', 1, 1),
(66,  '30.00', 1, 1),
(67,  '70.00', 1, 1),
(68,  '90.00', 1, 1),
(69,  '4100', 1, 1),
(70,   '', 1, 1),
(71,  '140.0', 1, 1),
(72,  '40.00', 1, 1),
(73,  '400.0', 1, 1),
(74,  '50.00', 1, 1),
(75,  '160.0', 1, 1),
(76,  '50.00', 1, 1),
(77,  '40.00', 1, 1),
(78,  '400.0', 1, 1),
(79,  '400.0', 1, 1),
(80,  '80.00', 1, 1),
(81,  '90.00', 1, 1),
(82,  '30.00', 1, 1),
(83,  '50.00', 1, 1),
(84,  '140.0', 1, 1),
(85,  '400.0', 1, 1),
(86,  '90.00', 1, 1),
(87,  '70.00', 1, 1),
(88,  '50.00', 1, 1),
(89,  '400.0', 1, 1),
(90,   '', 1, 1),
(91, '80.00', 1, 1),
(92, '900.0', 1, 1),
(93, '250.0', 1, 1),
(94, '15.00', 1, 1),
(94, '15.00', 1, 1),
(94, '15.00', 1, 1),
(94, '15.00', 1, 1),
(94, '15.00', 1, 1),
(95, '70.00', 1, 1),
(95, '70.00', 1, 1),
(96, '40.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(97, '60.00', 1, 1),
(98, '40.00', 1, 1),
(99, '150.0', 1, 1),
(100, '100.0', 1, 1),
(101,'50.0', 1, 1);

