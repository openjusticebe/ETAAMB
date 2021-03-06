TRUNCATE TABLE `docs`;
TRUNCATE TABLE `links_cache`;
TRUNCATE TABLE `render_cache`;
TRUNCATE TABLE `tag_links`;
TRUNCATE TABLE `tag_relations`;
TRUNCATE TABLE `tag_words`;
TRUNCATE TABLE `text`;
TRUNCATE TABLE `titles`;
TRUNCATE TABLE `types`;
INSERT INTO `types` (`type_nl`, `type_fr`) VALUES ('notype', 'notype');

TRUNCATE TABLE `tag_stopwords`;
INSERT INTO `tag_stopwords` (`id`, `ln`, `word`) VALUES
(1, 'nl', 'aan'),
(2, 'nl', 'af'),
(3, 'nl', 'al'),
(4, 'nl', 'alles'),
(5, 'nl', 'als'),
(6, 'nl', 'ben'),
(7, 'nl', 'bij'),
(8, 'nl', 'daar'),
(9, 'nl', 'dan'),
(10, 'nl', 'dat'),
(11, 'nl', 'de'),
(12, 'nl', 'der'),
(13, 'nl', 'deze'),
(14, 'nl', 'die'),
(15, 'nl', 'dit'),
(16, 'nl', 'doch'),
(17, 'nl', 'doen'),
(18, 'nl', 'door'),
(19, 'nl', 'dus'),
(20, 'nl', 'een'),
(21, 'nl', 'eens'),
(22, 'nl', 'en'),
(23, 'nl', 'er'),
(24, 'nl', 'ge'),
(25, 'nl', 'geen'),
(26, 'nl', 'haar'),
(27, 'nl', 'had'),
(28, 'nl', 'heb'),
(29, 'nl', 'hebben'),
(30, 'nl', 'heeft'),
(31, 'nl', 'hem'),
(32, 'nl', 'het'),
(33, 'nl', 'hier'),
(34, 'nl', 'hij'),
(35, 'nl', 'hoe'),
(36, 'nl', 'hun'),
(37, 'nl', 'iets'),
(38, 'nl', 'ik'),
(39, 'nl', 'in'),
(40, 'nl', 'is'),
(41, 'nl', 'ja'),
(42, 'nl', 'je'),
(43, 'nl', 'kan'),
(44, 'nl', 'kon'),
(45, 'nl', 'maar'),
(46, 'nl', 'me'),
(47, 'nl', 'meer'),
(48, 'nl', 'men'),
(49, 'nl', 'met'),
(50, 'nl', 'mij'),
(51, 'nl', 'mijn'),
(52, 'nl', 'moet'),
(53, 'nl', 'na'),
(54, 'nl', 'naar'),
(55, 'nl', 'niet'),
(56, 'nl', 'niets'),
(57, 'nl', 'nog'),
(58, 'nl', 'nu'),
(59, 'nl', 'of'),
(60, 'nl', 'om'),
(61, 'nl', 'omdat'),
(62, 'nl', 'ons'),
(63, 'nl', 'ook'),
(64, 'nl', 'op'),
(65, 'nl', 'over'),
(66, 'nl', 'reeds'),
(67, 'nl', 'te'),
(68, 'nl', 'tegen'),
(69, 'nl', 'toch'),
(70, 'nl', 'toen'),
(71, 'nl', 'tot'),
(72, 'nl', 'u'),
(73, 'nl', 'uit'),
(74, 'nl', 'uw'),
(75, 'nl', 'van'),
(76, 'nl', 'veel'),
(77, 'nl', 'voor'),
(78, 'nl', 'want'),
(79, 'nl', 'waren'),
(80, 'nl', 'was'),
(81, 'nl', 'wat'),
(82, 'nl', 'we'),
(83, 'nl', 'wel'),
(84, 'nl', 'werd'),
(85, 'nl', 'wezen'),
(86, 'nl', 'wie'),
(87, 'nl', 'wij'),
(88, 'nl', 'wil'),
(89, 'nl', 'zal'),
(90, 'nl', 'ze'),
(91, 'nl', 'zei'),
(92, 'nl', 'zelf'),
(93, 'nl', 'zich'),
(94, 'nl', 'zij'),
(95, 'nl', 'zijn'),
(96, 'nl', 'zo'),
(97, 'nl', 'zou'),
(98, 'nl', 'art'),
(99, 'nl', 'er'),
(100, 'nl', 'fin'),
(101, 'nl', 'publie'),
(102, 'fr', 'alors'),
(103, 'fr', 'au'),
(104, 'fr', 'aucuns'),
(105, 'fr', 'aussi'),
(106, 'fr', 'autre'),
(107, 'fr', 'avant'),
(108, 'fr', 'avec'),
(109, 'fr', 'avoir'),
(110, 'fr', 'bon'),
(111, 'fr', 'car'),
(112, 'fr', 'ce'),
(113, 'fr', 'cela'),
(114, 'fr', 'ces'),
(115, 'fr', 'ceux'),
(116, 'fr', 'chaque'),
(117, 'fr', 'ci'),
(118, 'fr', 'comme'),
(119, 'fr', 'comment'),
(120, 'fr', 'dans'),
(121, 'fr', 'de'),
(122, 'fr', 'des'),
(123, 'fr', 'du'),
(124, 'fr', 'dedans'),
(125, 'fr', 'dehors'),
(126, 'fr', 'depuis'),
(127, 'fr', 'deux'),
(128, 'fr', 'devrait'),
(129, 'fr', 'doit'),
(130, 'fr', 'donc'),
(131, 'fr', 'dos'),
(132, 'fr', 'droite'),
(133, 'fr', 'debut'),
(134, 'fr', 'elle'),
(135, 'fr', 'elles'),
(136, 'fr', 'en'),
(137, 'fr', 'encore'),
(138, 'fr', 'essai'),
(139, 'fr', 'est'),
(140, 'fr', 'et'),
(141, 'fr', 'eu'),
(142, 'fr', 'fait'),
(143, 'fr', 'faites'),
(144, 'fr', 'fois'),
(145, 'fr', 'font'),
(146, 'fr', 'force'),
(147, 'fr', 'haut'),
(148, 'fr', 'hors'),
(149, 'fr', 'ici'),
(150, 'fr', 'il'),
(151, 'fr', 'ils'),
(152, 'fr', 'je'),
(153, 'fr', 'juste'),
(154, 'fr', 'la'),
(155, 'fr', 'le'),
(156, 'fr', 'les'),
(157, 'fr', 'leur'),
(158, 'fr', 'la'),
(159, 'fr', 'ma'),
(160, 'fr', 'maintenant'),
(161, 'fr', 'mais'),
(162, 'fr', 'mes'),
(163, 'fr', 'mine'),
(164, 'fr', 'moins'),
(165, 'fr', 'mon'),
(166, 'fr', 'mot'),
(167, 'fr', 'meme'),
(168, 'fr', 'ni'),
(169, 'fr', 'nommes'),
(170, 'fr', 'notre'),
(171, 'fr', 'nous'),
(172, 'fr', 'nouveaux'),
(173, 'fr', 'ou'),
(174, 'fr', 'ou'),
(175, 'fr', 'par'),
(176, 'fr', 'parce'),
(177, 'fr', 'parole'),
(178, 'fr', 'pas'),
(179, 'fr', 'personnes'),
(180, 'fr', 'peut'),
(181, 'fr', 'peu'),
(182, 'fr', 'piece'),
(183, 'fr', 'plupart'),
(184, 'fr', 'pour'),
(185, 'fr', 'pourquoi'),
(186, 'fr', 'quand'),
(187, 'fr', 'que'),
(188, 'fr', 'quel'),
(189, 'fr', 'quelle'),
(190, 'fr', 'quelles'),
(191, 'fr', 'quels'),
(192, 'fr', 'qui'),
(193, 'fr', 'sa'),
(194, 'fr', 'sans'),
(195, 'fr', 'ses'),
(196, 'fr', 'seulement'),
(197, 'fr', 'si'),
(198, 'fr', 'sien'),
(199, 'fr', 'son'),
(200, 'fr', 'sont'),
(201, 'fr', 'sous'),
(202, 'fr', 'soyez'),
(203, 'fr', 'sujet'),
(204, 'fr', 'sur'),
(205, 'fr', 'ta'),
(206, 'fr', 'tandis'),
(207, 'fr', 'tellement'),
(208, 'fr', 'tels'),
(209, 'fr', 'tes'),
(210, 'fr', 'ton'),
(211, 'fr', 'tous'),
(212, 'fr', 'tout'),
(213, 'fr', 'trop'),
(214, 'fr', 'tres'),
(215, 'fr', 'tu'),
(216, 'fr', 'valeur'),
(217, 'fr', 'voie'),
(218, 'fr', 'voient'),
(219, 'fr', 'vont'),
(220, 'fr', 'votre'),
(221, 'fr', 'vous'),
(222, 'fr', 'vu'),
(223, 'fr', 'ca'),
(224, 'fr', 'etaient'),
(225, 'fr', 'etat'),
(226, 'fr', 'etions'),
(227, 'fr', 'ete'),
(228, 'fr', 'etre'),
(229, 'fr', 'art'),
(230, 'fr', 'er'),
(231, 'fr', 'fin'),
(232, 'fr', 'publie');
