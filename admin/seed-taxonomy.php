<?php
/**
 * One-time bulk import for the full vertebrates taxonomy (Amfibieën,
 * Reptielen, Vissen, Vogels, Zoogdieren), per the PDF hierarchy. Visit this
 * page once while logged in as admin; it creates the category tree and each
 * species as a draft "Dier" ready for photos. Safe to re-run — existing
 * categories/animals (matched on title + parent) are skipped, nothing is
 * duplicated or overwritten.
 */
require __DIR__.'/inc.php';

// name => [] (leaf category, optionally with species as a plain list)
//      or name => [subcategory tree...] (associative = more categories)
$tree = [
    'Gewervelde dieren' => [
        'Amfibieën' => [
            'Kikkers' => [
                'Aziatische hoornkikkers' => [],
                'Boomkikkers' => [
                    'Triprion spinosus','Trachycephalus resinifictrix','Phyllomedusa tomopterna',
                    'Phyllomedusa sauvagii','Phyllomedusa bicolor','Litoria caerulea','Cruziohyla craspedopus',
                ],
                'Echte kikkers' => ['Lithobates catesbeianus'],
                'Gouden kikkers' => ['Mantella aurantiaca'],
                'Padden' => ['Rhinella marina','Incilius coniferus','Aptelopus zeteki'],
                'Pijlgifkikkers' => [
                    'Phyliobates terribilis','Oophaga hustrionica','Dendrobates trinctorius azureus','Dendrobates leucomelas',
                ],
                'Schuimnestboomkikkers' => ['Rhacophorus dennysi'],
                'Smalbekkikkers' => ['Dyscophus guineti'],
                'Vuurbuikpadden' => ['Bombina orientalis'],
                'Zuid-Amerikaanse hoornkikkers' => ['Lepidobatrachus','Ceratophrys'],
            ],
            'Salamanders' => [
                'Echte salamanders' => ['Cynops orientalis'],
                'Molsalamanders' => ['Ambystoma mexicanum'],
            ],
            'Wormsalamanders' => [
                'Waterbewonende wormsalamanders' => ['Potomotyphlus'],
            ],
        ],
        'Reptielen' => [
            'Slangen' => [
                'Zandslangen' => ['Psammophis mossambicus','Malpolon insignitus'],
                'Waterslangen' => [
                    'Thamnophis sirtalis tetrataenia','Nerodia floridana','Natrix tessellata','Natrix natrix natrix','Natrix maura',
                ],
                'Python' => [
                    'Simalia boeleni','Simalia amethistina','Python regius','Python brongersmai','Python bivittatus',
                    'Python anchietae','Morelia viridis','Morelia spilota variegata','Morelia spilota cheynei',
                    'Morelia carinata','Morelia bredli','Morelia spilota spilota','Malayopython timoriensis',
                    'Malayopython reticulatus reticulatus','Liasis mackloti savuensis','Leiopython albertisii',
                    'Aspidites ramsayi','Apodora papuana',
                ],
                'Madagaskar slangen' => ['Langaha madagascariensis'],
                'Koraalslangachtigen' => [
                    'Oxyuranus microlepidotus','Ophiophagus hannah','Naja siamensis','Naja nivea',
                    'Naja nigricincta nigricincta','Naja naja','Naja melanoleuca','Naja kaouthia','Naja haje haje',
                    'Naja annulifera','Dendroaspis polylepis','Dendroaspis angusticeps','Acanthophis rugosus',
                    'Acanthophis anatarcticus',
                ],
                'Haakneusslangen' => ['Philodryas baroni','Hydrodynastes gigas','Heterodon nasicus'],
                'Groefkopadders' => [
                    'Trimeresurus venustus','Trimeresurus stejnegeri','Trimeresurus mcgregori','Trimeresurus insularis',
                    'Trimeresurus flavomaculatus','Trimeresurus albolabris','Sitrurus miliarius barbouri',
                    'Sistrurus miliarius','Sisturus catenatus','Protobothrops mangshanensis','Mixcoatlus melanurus',
                    'Lachesis muta muta','Lachesis melanocephala','Crotalus vegrandis','Crotalus tzabcan',
                    'Crotalus pyrrhus','Crotalus polystictus','Crotalus molossus molossus','Crotalus horridus',
                    'Crotalus cerastes','Crotalus catalinensis','Crotalus atrox','Crotalus ademteus',
                    'Craspedocephalus trigonocephalus','Bothrops moojeni','Bothrops asper','Bothriechis schlegelii cf',
                    'Atropoides mexicanus','Agkistrodon contrortix','Agkistrodon conanti','Agkistrodon bilineatus',
                ],
                'Echte gladde slangen' => [
                    'Zamenis situla','Zamenis longissimus','Spilotes pullatus','Pituophis melanoleucus mugitus',
                    'Phrynonax poecilonotus','Pantherophis spiloides','Pantherophis guttatus','Pantherophis alleghaniensis',
                    'Lampropeltis triangulum syspila','Lampropeltis triangulum hondurensis','Lampropeltis prymelana',
                    'Lampropeltis polyzona','Lampropeltis getula floridana','Lampropeltis getula californiae',
                    'Lampropeltis calligaster','Lampropeltis alterna','Lampropeltis abnorma','Gonysoma oxycephalum',
                    'Gonysoma boulengeri','Elaphe taeniura ridleyi','Elaphe taeniura friesei','Elaphe schrenckii',
                    'Elaphe quatuorlineata','Elaphe moelendorffi','Elaphe carinata carinata','Drymarchon melanurus unicolor',
                    'Drymarchon couperi','Dispholidus typus','Dasypeltis scabra','Boiga dendrophilla dendrophilla',
                ],
                'Echte adders' => [
                    'Vipera berus berus','Vipera ammodytes meridionalis','Vipera ammodytes ammodytes',
                    'Cerastes cerastes cerastes','Bitis rhinoceros','Bitis parviocula','Bitis nasicornis',
                    'Bitis gabionica','Bitis cornuta','Bitis arientans','Atheris squamigera','Atheris nitschei',
                ],
                "Boa's" => [
                    'Sazinia madagascariensis','Lichanura trivirgata gracia','Eunectes notaeus','Eunectes murinus',
                    'Eryx colubrinus loveridgei','Epicrates cenchria','Corallus hortulana','Corallus caninus',
                    'Corallus batesii','Corallus annulatus','Chilabothrus subflavus','Chilabothrus inoratus',
                    'Chilabothrus angulifer','Boa constrictor imperator','Boa constrictor constricto',
                    'Acrantophis madagascariensis','Acrantophis dumerlili',
                ],
            ],
            'Schildpadden' => [
                'Modder- en muskusschildpadden' => ['Staurotypus salvinii'],
                'Bijtschildpadden' => ['Macrochelys temminckii'],
                'Scheenplaatschildpadden' => ['Podocnemis erythrocephala','Podocnemis expansa'],
                'Oude wereld moerasschildpadden' => ['Cuora mouhotii'],
                'Moerasschildpadden' => ['Emys orbicularis','Trachemys scripta elegans','Terrapene carolina bauri','Trachemys scripta scripta'],
                'Landschildpadden' => ['Aldabrachelys gigantea','Astrochelys radiata','Chersina angulata','Gopherus polyphemus','Pyxis arachnoides'],
            ],
            'Krokodilachtigen' => [
                'Alligators, kaaimannen en gavialen' => ['Alligator mississippiensis','Alligator sinesis','Caiman crocodilus crocodilus','Caiman crocodilus','Caiman latrostris','Caiman yacare'],
                'Krokodillen' => [
                    'Crocodylus acutus','Crocodylus intermedius','Crocodylus mindorensis','Crocodylus niloticus','Mecistops cataphractus',
                    'Osteolaemus tertaspis','Crocodylus moreletii','Crocodylus rhombifer','Crocodylus haili','Crocodylus johnstoni',
                    'Crocodylus niloticus chamses','Crocodylus niloticus cowei','Crocodylus novaeguineae','Crocodylus palustris',
                    'Crocodylus porosus','Crocodylus siamensis','Crocodylus suchus',
                ],
            ],
            'Hagedissen' => [
                'Varanen' => ['Varanus komodoensis','Varanus acanthurus','Varanus glauerti','Varanus varius','Varanus melinus','Varanus prasinus'],
                'Stekelleguanen en padhagedissen' => ['Phrynosoma asio','Phrynosoma cornutum','Sceloporus malachiticus','Sceloporus taeniocnemis'],
                'Nieuw-Zeelandse gekko\'s' => ['Naultinus grayii'],
                'Korsthagedissen' => ['Heloderma horridum exasperatum','Heloderma suspectum'],
                'Hazelwormen' => ['Abronia graminae'],
                'Gordelstaarthagedissen' => ['Namazonurus lawrenci','Ouroborus cataphractus','Smaug depressus'],
                'Dwerggekko\'s en wondergekko\'s' => ['Pristurus carteri'],
                'Basilisken' => ['Basiliscus plumifrons','Corytophanes cristatus'],
                'Anolissen' => ['Anolis equestris equestris','Anolis smallwoodi'],
                'Teju\'s' => ['Crocodilurus amazonicus','Dracaena guianensis'],
                'Leguanen' => ['Iguana iguana','Ctenosaura quinquecarinata'],
                'Kameleons' => [
                    'Kinyongia vosseleri','Brookesia stumpffi','Calumma parsonii parsonii','Furcifer oustaleti','Furcifer pardalis',
                    'Kinyongia fischeri','Kinyongia matschiei','Rhampholeon acuminatus','Rieppeleon kerstenii','Trioceros deremensis',
                    'Trioceros jacksonii xantholophus',
                ],
                'Echte gekko\'s' => ['Phelsuma grandis','Ptenopus carpi','Lygodactylus williamsi'],
                'Chinese krokodilstaarthagedissen' => ['Shinisaurus crocodilurus crocodilurus'],
                'Agamen' => ['Physignathus cocincinus','Chlamysdosaurus kingii','Pogona vitticeps'],
            ],
        ],
        'Vissen' => [
            'Straalvinnigen' => [
                'Longvissen' => [],
                'Kwastsnoeken' => [],
                'Cichliden' => [],
                'Beentongvissen' => [],
                'Arowana\'s' => [],
                'Trekkervissen' => ['Balistoides conspicillum','Odonus niger'],
                'Mesvissen' => ['Chitala ornata','Xenomystus nigri'],
                'Kogelvisachtigen' => [
                    'Maanvissen' => ['Oceanario de lisboa'],
                    'Zoetwatergobies' => ['Periophthalmus barbarus'],
                ],
                'Schorpioenvisachtigen' => [
                    'Schorpioenvissen' => ['Rhinopias frondosa'],
                    'Steenvissen' => ['Synanceia verrucosa'],
                    'Papagaaivissen' => ['Scarus quoyi'],
                ],
                'Palingachtigen' => [
                    'Murenen' => ['Echidna nebulosa','Gymnothorax funebris','Gymnothorax zebra','Rhinomuraena quasita'],
                    'Mesalen' => ['Electrophorus electicus'],
                    'Meervallen' => ['Silurus glanis'],
                    'Lipvissen' => ['Cheilinus undulatus','Cirrhilabrus aquamarinus','Coris gaimerd','Labrus bergylta','Bodianus rufus'],
                    'Kardinaalbaarzen' => ['Pterapogon kauderni'],
                ],
                'Baarsachtigen' => [
                    'Zeebarbelen' => ['Parupeneus multifasciatus'],
                    'Wimpelvis' => ['Zanclus cornotus'],
                    'Zilverbladvissen' => ['Monodactylus argenteus'],
                    'Zaag- en zeebaarzen' => ['Cromileptes altivelis','Epinephelus lanceolatus','Cephalopholis argus','Epinephelus marginatus','Paralabrax clathratus'],
                    'Snappers' => ['Lutjanus sebae','Lutjanus kasmira'],
                    'Rifwachters' => ['Calloplesiops altivelis'],
                    'Koraalvlinders' => [
                        'Chaetodon falcula','Chaetodon sedentarius','Chelmon muelleri','Chelmon rostratus','Coradion melanopus',
                        'Hemitaurichthys polylepis','Prognathodes aculeatus','Chaetodon capistratus','Chaetodon semilarvatus',
                        'Chaetodon zanzibarensis','Forcipiger flavissimus',
                    ],
                    'Keizersvissen' => ['Holacanthus ciliaris','Pomacanthus imperator','Pomacanthus paru','Centropyge bicolor'],
                    'Grombaarzen' => ['Plectorhinchus albovittatus','Plectorhinchus vittatus'],
                    'Doktervissen' => ['Acanthurus dussumieri','Acanthurus triostegus','Acanthurus xanthopterus','Zebrasoma flavescens','Acanthurus sohal'],
                ],
            ],
            'Kraakbeenvissen' => [
                'Haaien' => [
                    'Hamerhaaien' => ['Sphyrna tiburo','Sphyrna lewine','Sphyrna mokarran'],
                    'Bamboehaaien' => ['Chiloscyllium arabicum','Chiloscyllium punctatum','Hemiscyllium ocellatum','Chiloscyllium griseum','Chiloscyllium plagiosum','Hemiscyllium trispeculare'],
                    'Blinde haaien' => ['Brachaelurus waddi'],
                    'Doornhaaiachtigen' => ['Oxynotus centrina'],
                    'Gladde haaien' => ['Mustelus asterias','Triakis scyllium','Triakis semifasciata','Galeorhinus galeus','Mustelus californicus','Mustelus mustelus'],
                    'Kathaaien' => ['Atelomycterus marmoratus','Cephaloscyllium ventriosum','Scyliorhinus stellaris','Atelomycterus baliensis','Poroderma africanum','Scyliorhinus canicula'],
                    'Makreelhaaien' => ['Carcharias taurus'],
                    'Requiemhaaien' => [
                        'Carcharhinus acronotus','Carcharhinus albimargunatus','Carcharhinus amblyrhynchos','Carcharhinus humani',
                        'Carcharhinus leucas','Carcharhinus melanopterus','Carcharhinus perezii','Carcharhinus plumbeus',
                        'Negaprion acutidens','Triaenodon obesus','Carcharhinus falciformis','Carcharhinus limbatus','Negaprion brevirostris',
                    ],
                    'Tijgerhaaien' => ['Galeocerdo cuvier'],
                    'Varkenshaaien' => ['Heterodontus francisci','Heterodontus portusjacksoni','Heterodontus galeatus','Heterodontus japonicus','Heterodontus zebra'],
                    'Verpleegsterhaaien' => ['Ginglymostoma cirratum','Nebrius ferrugineus','Pseudoginglymostoma'],
                    'Walvishaaien' => ['Rhincodon typus'],
                    'Wobbegons' => ['Orectolobus maculatus','Orectolobus reticulatus','Orectolobus wardi','Eucrossorhinus dasypogon','Orectolobus hutchinson','Orectolobus japonicus','Orectolobus ornatus'],
                    'Zebrahaaien' => ['Stegostoma tigrinum'],
                    'Zee-engelen' => ['Squantina japonica'],
                ],
                'Roggen' => [
                    'Vioolroggen' => ['Trygonorrhina dumerilii'],
                    'Adelaarsroggen' => [],
                    'Amerikaanse doornroggen' => [],
                    'Echte roggen' => [],
                    'Pijlstaartroggen' => [],
                    'Sidderroggen' => [],
                    'Zaagvissen' => [],
                    'Zoetwaterroggen' => [],
                ],
                'Draakvissen' => ['Hydrolagus coliei'],
            ],
        ],
        'Vogels' => [
            'Fazantachtigen' => [
                'Bospatrijzen' => ['Arborophila gingica'],
                'Patrijzen, kwartels en frankolijnen' => ['Synoicus chinensis','Margaroperdix madagascariensis'],
                'Pauwen' => ['Alfopavo congensis'],
                'Tragopanen en glansfazanten' => ['Tragopan caboti'],
            ],
            'Neushoornvogels' => [
                'Hoornraven' => ['Bucorvus abyssinicus'],
                'Echte neushoornvogels' => ['Bycanistes bucinator','Ceratogymna atrata','Buceros bicornis','Rhyticeros cassidix','Tockus deckeni'],
                'Hoppen' => ['Upupa epops'],
                'Boomhoppen' => [],
            ],
            'Papegaaiachtigen' => [
                'Afrikaanse papagaaien' => ['Poicephalus senegalus senegalus','Psittacus erithacus erithacus'],
                'Amazonepapegaaien' => ['Amazona oratrix magna'],
                'Ara\'s' => ['Anodorhynchus hyacinthinus','Ara ararauna','Ara chloropterus','Ara hybride','Ara militaris mexicana','Primolius maracana','Ara severus','Primolius coulino'],
                'Dwergpapegaaien' => ['Agapornis nigrigenis','Agapornis roseicollis'],
                'Kaketoes' => ['Cacatua alba','Cacatua citrinocristata','Nymphicus hollandicus'],
                'Lori\'s' => ['Glossoptilus goldlei','Hypocharmosyna placentis','Vini australis','Lorius hypoinochrous devittatus','Melopsittacus undulatus'],
                'Muspapegaaien' => ['Pionites melanocephalus'],
                'Nieuw-Zeelandse papegaaien' => ['Nestor notabilis'],
                'Oceanische papegaaien' => ['Lathamus discolor','Neopsephotes bourkii'],
                'Parkieten' => ['Aratinga nenday','Aratinga solstitialis','Pyrrhura griseipectus','Psittacara acuticaudatus','Guarouba guarouba'],
                'Eilandpapegaaien' => ['Coracopsis nigra nigra'],
                'Koningsparkieten en halsbandparkiet' => ['Lathamus discolor','Psittacula eupatria'],
            ],
            'Pelikaanachtigen' => [
                'Hamerkop' => ['Scopus umbretta umbrutta'],
                'Ibissen' => ['Eudocimus ruber','Geronticus calvus','Geronticus eremita','Bostrychia hagedash'],
                'Pelikanen' => ['Pelecanus conspicillatus','Pelecanus crispus','Pelecanus erythorhynchos','Pelecanus thagus','Pelecanus occidentalis carolinensis'],
                'Lepelaars' => ['Platalea leucorodia leucorodia'],
                'Schoenbekooievaar' => ['Balaeniceps rex'],
                'Reigers' => [],
            ],
            'Spechtvogels' => [
                'Afrikaanse baardvogels' => ['Lybius dubius'],
                'Toekans' => ['Ramphastos tucanus tucanus','Pteroglossus castanotis'],
            ],
            'Zangvogels' => [
                'Brilvogels' => ['Zosterops eurycricotus'],
                'Gaailijsters' => ['Leiothrix lutea'],
                'Honingeters' => ['Entomyzon cyanotis'],
                'Lijsters' => ['Geokichla dohertyi'],
                'Kraaien' => ['Cyanocorax chrysops'],
            ],
            'Eendvogels' => [
                'Echte eenden' => ['Aix galecirulata','Anas platyrhynchos platyrhynchos','Mergus squamatus'],
                'Fluiteenden' => ['Dendrocygna bicolor','Dendrocygna viduata'],
                'Halfganzen' => ['Sarkidiornis melanotos'],
                'Zwanen en ganzen' => ['Branta leucopsis'],
            ],
            'Hoendervogels' => [
                'Tandkwartels' => ['Callipepla californica','Cyrtonyx montezumae'],
                'Sjakohoenders en hokko\'s' => ['Crax blumenbachii','Crax rubra rubra'],
            ],
            'Kraanvogelachtigen' => [
                'Kraanvogels' => ['Balearica pavonina pavonina'],
                'Trompetvogels' => ['Psophia crepitans crepitans'],
            ],
            'Roofvogels' => [
                'Gieren van de nieuwe wereld' => ['Cathartes aura','Vultur gryphus'],
                'Gieren van de oude wereld' => ['Gypaetus barbatus barbatus','Gyps fulvus fulvus','Gyps rueppellii','Necrosyrtes monachus','Neophron percnopterus percnopterus','Aegypius monachus'],
                'Harpijarenden' => ['Harpia harpyja'],
                'Secretarisvogel' => ['Sagittarius serpentarius'],
                'Slangenarenden' => ['Terathopius ecaudatus'],
                'Uilen' => ['Athene cunicularia','Bubo bubo bubo','Bubo scandiacus','Ketupa ketupu ketupu','Megascops asio','Pulsatrix perspicillata'],
                'Valken en caracara\'s' => ['Caracara plancus','Falco naumanni','Falco tinnuculus tinnuculus','Phalcoboenus australis'],
                'Zeearenden en wouwen' => ['Haliaeetus albicilla albicilla','Haliaeetus leucocephalus','Haliaeetus pelagicus'],
                'Buizerds' => [],
                'Echte arenden' => [],
                'Visarenden' => [],
            ],
            'Scharrelaarvogels' => [
                'IJsvogels' => ['Dacelo novaeguineae'],
                'Scharrelaars' => ['Coracias cyanogaster'],
                'Bijeneters' => [],
                'Grondscharrelaars' => [],
                'Motmots' => [],
            ],
            'Steltloperachtigen' => [
                'Krokodilwachter' => ['Pluvianus aegyptius'],
                'Vechtkwartels' => ['Turnix suscitator'],
            ],
            'Duiven' => [
                'Duiven en tortels' => ['Columba guinea','Spilopelia senegalensis','Streptopelia turtur turtur'],
                'Amerikaanse grondduiven' => ['Claravis pretiosa'],
                'Australische duiven' => ['Gallicolumba rufigula rufigula','Geopella cuneata','Phaps elegans','Chalcophaps indica','Leucosarcia melanoleuco'],
                'Aziatische duiven' => ['Otidiphaps aruensis'],
                'Fruitduiven en muskaatduiven' => ['Ducula mullerii mullerii'],
            ],
            'Flamingo\'s' => ['Phoenicopterus ruber ruber'],
            'Pinguïns' => ['Aptenodytes patagonicus','Pygoscelis papua papua','Eudyptes chrysolophus','Spheniscus demersus'],
            'Kiwi\'s' => ['Apteryx mantelli'],
            'Kolibries' => ['Amazilia amazilia'],
            'Ooievaars' => ['Ciconia abdimii','Leptoptilos crumenifer','Mycteria ibis'],
            'Struisvogels' => ['Struthio camelus'],
            'Tinamoes' => ['Crypturellus tataupa','Eudromia elegans'],
            'Zonneral en kagoe' => ['Eurypyga helias helias'],
            'Kasuarissen en emoes' => [],
        ],
        'Zoogdieren' => [
            'Evenhoevigen' => [
                'Bokken' => ['Budorcas taxicolor taxicolor','Capra falconeri heptneri','Hemitragus jemlahicus'],
                'Holhoornigen' => ['Tragelaphus eurycerus isaaci'],
                'Kamelen en lama\'s' => ['Camelus dromedarius','Camelus ferus bactrianus','Lama pacos'],
                'Duikers' => ['Cephalophus natalensis','Cephalophus silvicultor'],
                'Dwergherten' => ['Tragulus nigricans'],
                'Echte antilopen' => ['Madoqua kirkii'],
                'Echte herten' => ['Dama dama','Muntiacus reevesi'],
                'Giraffen' => ['Okapia johnstoni'],
                'Nijlpaarden' => ['Hippopotamus amphibius'],
                'Paardantilopen' => ['Hippotragus niger niger'],
                'Runderen' => ['Bubalus depressicornis','Bison bison bison','Bos javanicus javannicus','Syncerus caffer caffer'],
                'Schijnherten' => ['Rangifer tarandus'],
                'Varkens' => ['Babyrousa'],
            ],
            'Walvisachtigen' => [
                'Dolfijnen' => ['Orcinus orca','Tursiops truncatus truncatus'],
                'Gronddolfijnen' => ['Delphinapterus leucas'],
            ],
            'Primaten' => [
                'Brul- en slingerapen' => ['Ateles fusciceps rufiventris','Lagothrix lagotricha'],
                'Gibbons' => ['Nomascus leucogenys'],
                'Hondsapen' => ['Cercopithecus hamlyni','Colobus guerza','Macara mulatta','Macara nigra','Mandrillus sphinx','Papio hamadryas','Semnopithecus entellus','Trachypithecus auratus'],
                'Kapucijnapen' => ['Saimiri boliviensis boliviensis','Sapajus apella'],
                'Klauwaapjes' => ['Callimico goeldii','Callithrix geoffroyi','Callithrix jacchus','Cebuella pygmaea','Leontopithecus chrysomelas','Mico argentatus','Saguinus imperator subgrisescens','Saguinus oedipus'],
                'Lori\'s' => ['Loris lydekkerianus nordicus'],
                'Maki\'s' => ['Eulemur flavifrons','Eulemur macaco','Lemur catta','Varecia rubra','Eulemur coronatus'],
                'Mensapen' => ['Gorilla beringei graueri','Gorilla gorilla gorilla','Pan paniscus','Pan troglodytes','Pongo pygmaeus'],
                'Sifaka\'s' => ['Propithecus coronatus'],
                'Titi\'s en saki\'s' => ['Callicebus cupreus','Chiropotes sagulatus','Pithecia pithecia'],
            ],
            'Roofdieren' => [
                'Oorrobben' => ['Zalophus californiacus'],
                'Beren' => ['Ailuropoda melanoleuca','Melursus ursinus ursinus','Tremarctos ornatus','Ursus americanus'],
                'Civetkatten' => ['Genetta genetta'],
                'Grote katten' => ['Panthera leo','Panthera leo krugeri','Panthera pardus orientalis','Panthera onca','Panthera tigris altaica','Panthera tigris sumatrae'],
                'Hondachtigen' => ['Lycaon pictus','Urocyon cinereoargenteus','Vulpes zerda'],
                'Kleine katten' => ['Lynx rufus','Puma concolor coryl'],
                'Mangoesten' => ['Cynictis penicillata','Suricata suricatta'],
                'Marterachtigen' => ['Enhydra lutris kenyoni','Lontra canadensis','Pteronura brasiliensis'],
                'Stinkdieren' => ['Mephitis mephitis'],
                'Wasbeerachtigen' => ['Nasau nasau','Procyon lotor'],
                'Rode panda\'s' => ['Ailurus fulgens'],
                'Zeehonden' => ['Phoca vitulina vitulina'],
            ],
            'Gordeldieren' => ['Chaetophractus villosus'],
            'Klimbuideldieren' => [
                'Kangoeroes' => ['Dendrolagus goodfellowi buergersi'],
            ],
            'Knaagdieren' => [
                'Caviaachtigen' => ['Cavia porcellus domesticus','Dolichotis patagonum'],
                'Eekhoornachtigen' => ['Cynomys ludovicianus'],
                'Goendi\'s' => ['Ctenodactylus gundi'],
                'Ratten en muizen' => ['Phloeomys pallidus','Lemniscomys barbarus','Mus musculus','Rattus norvegicus'],
                'Stekelvarkens van de nieuwe wereld' => ['Erethizon dorsatum'],
                'Beverratten en hutia\'s' => ['Myocastor coypus'],
            ],
            'Onevenhoevigen' => [
                'Neushoorns' => ['Ceratotherium simum simum','Rhinoceros unicornis'],
                'Paardachtigen' => ['Equus quagga boehmi','Equus zebra hartmannae'],
                'Tapirs' => ['Tapirus indicus'],
            ],
            'Tandarmen' => [
                'Miereneters' => ['Tamandua tetradactyla'],
                'Luiaards' => ['Choloepus didactylus'],
            ],
            'Wombats' => [],
            'Aardvarkens' => ['Orycteropus afer'],
            'Koala\'s' => ['Phascolarctos cinereus'],
            'Olifanten' => ['Elephas maximus'],
            'Springspitsmuizen' => ['Rhynchocyon petersi adersi'],
        ],
    ],
];

function seed_unique_slug($table, $baseSlug){
    $slug = $baseSlug; $i = 2;
    $chk = db()->prepare("SELECT COUNT(*) c FROM $table WHERE slug=?");
    while(true){
        $chk->execute([$slug]);
        if((int)$chk->fetch()['c'] === 0) return $slug;
        $slug = $baseSlug.'-'.$i; $i++;
    }
}

// A leaf is a plain (non-associative / numerically indexed) array — its
// entries are species names, not further category names.
function seed_is_species_list($arr){
    if(!is_array($arr)) return false;
    return array_keys($arr) === range(0, count($arr)-1);
}

$stats = ['categories'=>0, 'categories_skipped'=>0, 'animals'=>0, 'animals_skipped'=>0];

function seed_walk($node, $parentId, &$stats){
    foreach($node as $name => $value){
        // find existing category with this title+parent first (idempotent re-runs)
        $st = db()->prepare('SELECT id FROM categories WHERE title=? AND '.($parentId===null?'parent_id IS NULL':'parent_id=?'));
        $params = $parentId===null ? [$name] : [$name, $parentId];
        $st->execute($params);
        $existing = $st->fetch();
        if($existing){
            $catId = (int)$existing['id'];
            $stats['categories_skipped']++;
        } else {
            $slug = seed_unique_slug('categories', slugify($name));
            $ins = db()->prepare('INSERT INTO categories(title, slug, parent_id, blocks, published) VALUES(?,?,?,?,0)');
            $ins->execute([$name, $slug, $parentId, '[]']);
            $catId = (int)db()->lastInsertId();
            $stats['categories']++;
        }

        if(seed_is_species_list($value)){
            foreach($value as $species){
                $species = trim($species);
                if($species === '') continue;
                $chk = db()->prepare('SELECT id FROM animals WHERE title=? AND category_id=?');
                $chk->execute([$species, $catId]);
                if($chk->fetch()){ $stats['animals_skipped']++; continue; }
                $slug = seed_unique_slug('animals', slugify($species));
                $ins = db()->prepare('INSERT INTO animals(title, slug, blocks, published, category_id) VALUES(?,?,?,0,?)');
                $ins->execute([$species, $slug, '[]', $catId]);
                $stats['animals']++;
            }
        } else {
            seed_walk($value, $catId, $stats);
        }
    }
}

$done = false;
$published = 0;
$publishedAnimals = 0;
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verify();
    $action = $_POST['action'] ?? 'import';
    if($action === 'publish_categories'){
        $published = db()->exec('UPDATE categories SET published=1 WHERE published=0');
    } elseif($action === 'publish_all'){
        $published = db()->exec('UPDATE categories SET published=1 WHERE published=0');
        $publishedAnimals = db()->exec('UPDATE animals SET published=1 WHERE published=0');
    } else {
        seed_walk($tree, null, $stats);
        $done = true;
    }
}

admin_header('Taxonomie importeren', '');
?>
<div class="a-card"><div class="a-card-pad">
<?php if($published || $publishedAnimals): ?>
  <div class="notice"><?=$published?> categorieën<?php if($publishedAnimals): ?> en <?=$publishedAnimals?> dieren<?php endif; ?> gepubliceerd. Alles staat nu live op de site.</div>
<?php endif; ?>
<?php if($done): ?>
  <div class="notice">Klaar. <?=$stats['categories']?> nieuwe categorieën aangemaakt (<?=$stats['categories_skipped']?> bestonden al), <?=$stats['animals']?> nieuwe dieren aangemaakt als concept (<?=$stats['animals_skipped']?> bestonden al).</div>
  <p>Standaard blijven nieuwe soorten concept (nog geen foto). Klik hieronder op "Alles publiceren" om de hele boom — categorieën én soorten — meteen live te zetten, ook zonder dat er al foto's op staan.</p>
<?php else: ?>
  <h2 style="margin-top:0">Taxonomie importeren (Amfibieën, Reptielen, Vissen, Vogels, Zoogdieren)</h2>
  <p>Dit maakt in één keer de volledige categorieboom aan zoals in de PDF, plus elke soort als een nieuw concept-dier (categorie toegekend, nog geen foto — dat doe je zelf na dit importeren). Bestaande categorieën/dieren met dezelfde naam worden overgeslagen, dus dit is veilig om nogmaals te draaien — al eerder geïmporteerde soorten (amfibieën, slangen) blijven ongemoeid, enkel de nieuwe takken worden toegevoegd.</p>
  <form method="post">
    <?=csrf_field()?>
    <input type="hidden" name="action" value="import">
    <button class="a-btn" type="submit">Importeren</button>
  </form>
<?php endif; ?>
</div></div>
<?php if($done || $published || $publishedAnimals): ?>
<div class="a-card"><div class="a-card-pad">
  <form method="post" style="display:inline">
    <?=csrf_field()?>
    <input type="hidden" name="action" value="publish_all">
    <button class="a-btn" type="submit">Alles publiceren (categorieën + dieren)</button>
  </form>
  <form method="post" style="display:inline">
    <?=csrf_field()?>
    <input type="hidden" name="action" value="publish_categories">
    <button class="a-btn a-btn-ghost" type="submit">Enkel categorieën publiceren</button>
  </form>
  <a class="a-btn a-btn-ghost" href="content.php?type=category">Naar Categorieën</a>
  <a class="a-btn a-btn-ghost" href="content.php?type=animal">Naar Dieren</a>
</div></div>
<?php endif; ?>
<?php admin_footer(); ?>
