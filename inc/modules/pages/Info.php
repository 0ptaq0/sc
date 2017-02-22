<?php

    return [
        'name'          =>  $core->lang['pages']['module_name'],
        'description'   =>  $core->lang['pages']['module_desc'],
        'author'        =>  'Sruu.pl',
        'version'       =>  '1.0',
        'icon'          =>  'file',

        'install'       =>  function() use($core)
        {
            $core->db()->pdo()->exec("CREATE TABLE IF NOT EXISTS `pages` (
                `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT,
                `title` text NOT NULL,
                `slug` text NOT NULL,
                `desc` text NULL,
                `lang` text NOT NULL,
                `template` text NOT NULL,
                `date` text NOT NULL,
                `content` text NOT NULL,
                `markdown` INTEGER DEFAULT 0
            )");
            
            // Home - EN
            $core->db()->pdo()->exec("INSERT INTO `pages` (`title`, `slug`, `desc`, `lang`, `template`, `date`, `content`)
                VALUES ('Home', 'home', 'Praesent in metus id purus accumsan posuere.', 'en_english', 'index.html', datetime('now'),
                '<p>You wanna know how I got these scars? My father was… a drinker, and a fiend. And one night, he goes off crazier than usual. Mommy gets the kitchen knife to defend herself. He doesn’t like that, not one bit. So, me watching he takes the knife to her, laughing while he does it. He turns to me and he says: “Why so serious?”. He comes at me with the knife “Why so serious?”. He sticks the blade in my mouth. “Let’s put a smile on that face.” and… Why so serious?</p>
                <p>But we’ve met before. That was a long time ago, I was a kid at St. Swithin’s, It used to be funded by the Wayne Foundation. It’s an orphanage. My mum died when I was small, it was a car accident. I don’t remember it. My dad got shot a couple of years later for a gambling debt. Oh I remember that one just fine. Not a lot of people know what it feels like to be angry in your bones. I mean they understand. The fosters parents. Everybody understands, for a while. Then they want the angry little kid to do something he knows he can’t do, move on. After a while they stop understanding. They send the angry kid to a boy’s home, I figured it out too late. Yeah I learned to hide the anger, and practice smiling in the mirror. It’s like putting on a mask. So you showed up this one day, in a cool car, pretty girl on your arm. We were so excited! Bruce Wayne, a billionaire orphan? We used to make up stories about you man, legends and you know with the other kids, that’s all it was, just stories, but right when I saw you, I knew who you really were. I’d seen that look on your face before. It’s the same one I taught myself. I don’t why you took the fault for Dent’s murder but I’m still a believer in the Batman. Even if you’re not...</p>')
            ");
            
            // Home - PL
            $core->db()->pdo()->exec("INSERT INTO `pages` (`title`, `slug`, `desc`, `lang`, `template`, `date`, `content`)
                VALUES ('Home', 'home', 'Praesent in metus id purus accumsan posuere.', 'pl_polski', 'index.html', datetime('now'),
                '<p>Litwo! Ojczyzno moja! Ty jesteś jak zdrowie. Nazywał się imion wywabi pamięć droga co je tak rzuciły. Tuż i Bernatowicze, Kupść, Gedymin i sprzeczki. W biegu dotknęła blisko siebie czuł choroby zaród. Krzyczano na polu szukała kogoś posadzić na jutro na błoni i stryjaszkiem jedno puste miejsce wejrzenie odgadnął zaraz, czyim miało być siedzeniem. Rumienił się, wieczerzę przy boku sąsiadki a mój Rejencie, prawda, bez trzewika była ekonomowi poczciwemu świętą. Bo nie daje z czego wybrać u Woźnego lepiej zna się tłocz i długie zwijały się na tem, Że ojciec w domu ziemię orzę gdy Sędziego służono niedbale. Słudzy czekają, nim psów gromada. Gracz szarak! skoro pobył mało w nią śrut cienki! Trzymano wprawdzie pękła jedna króluje postać, jak od siebie czuł się uczyli. u nas. Do zobaczenia! tak nie przeczym, że posiadłość tam wódz gospodarstwa obmyśla wypraw w Tabor w miechu. Starzy na dzień postrzegam, jak gdyby ożył? Wróciłby do stolicy dajem i ust nie zmruża jako wódz gospodarstwa obmyśla wypraw w pole psy tuż, i byle nie zarzuci, bym uchybił kom w charta. Tak każe u jednej dwórórki.</p>
                <p>Kusym o śmierci syna. Brał dom żałobę, ale nic - rzekł z uśmiechem, a na szalach żebyśmy nasz ciężar poznali musim kogoś czekało. Stryj nieraz nowina, niby zakryty od rana w kupie pstręk na tem nic - Tadeuszowi wrzasnął tuż na szańcach Pragi, na koniu jeździł, pieszo do których później dowiedzieć się przyciągnąć do tych pól malowanych zbożem rozmaitem wyzłacanych pszenicą, posrebrzanych żytem. Gdzie bursztynowy świerzop, gryka jak dziecko do złotego run on żył, co o muzyce, o porządku, nikt mężczyzn i stoi wypisany każdy mimowolnie porządku pilnował.</p>')
            ");
            
            // About - EN
            $core->db()->pdo()->exec("INSERT INTO `pages` (`title`, `slug`, `desc`, `lang`, `template`, `date`, `content`)
                VALUES ('About me', 'about-me', 'Maecenas cursus accumsan est, sed interdum est pharetra quis.', 'en_english', 'index.html', datetime('now'),
                '<p>My name is Merely Ducard but I speak for Ra’s al Ghul… a man greatly feared by the criminal underworld. A mon who can offer you a path. Someone like you is only here by choice. You have been exploring the criminal fraternity but whatever your original intentions you have to become truly lost. The path of a man who shares his hatred of evil and wishes to serve true justice. The path of the League of Shadows.</p>
                <p>Every year, I took a holiday. I went to Florence, this cafe on the banks of the Arno. Every fine evening, I would sit there and order a Fernet Branca. I had this fantasy, that I would look across the tables and I would see you there with a wife maybe a couple of kids. You wouldn’t say anything to me, nor me to you. But we would both know that you’ve made it, that you were happy. I never wanted you to come back to Gotham. I always knew there was nothing here for you except pain and tragedy and I wanted something more for you than that. I still do.</p>')
            ");
            
            // About - PL
            $core->db()->pdo()->exec("INSERT INTO `pages` (`title`, `slug`, `desc`, `lang`, `template`, `date`, `content`)
                VALUES ('O mnie', 'about-me', 'Maecenas cursus accumsan est, sed interdum est pharetra quis.', 'pl_polski', 'index.html', datetime('now'),
                '<p>O, jak drudzy i świadki. I też same szczypiąc trawę ciągnęły powoli pod Twoją opiek ofiarowany, martwą podniosłem powiek i na kształt ogrodowych grządek: Że architekt był legijonistą przynosił kości stare na nim widzi sprzęty, też nie rozwity, lecz podmurowany. Świeciły się nagłe, jej wzrost i goście proszeni. Sień wielka jak znawcy, ci znowu w okolicy. i narody giną. Więc zbliżył się kołem. W mym domu przyszłą urządza zabawę. Dał rozkaz ekonomom, wójtom i w tkackim pudermanie). Wdział więc, jak wytnie dwa smycze chartów przedziwnie udawał psy tuż na polu szukała kogoś okiem, daleko, na Ojczyzny.</p>
                <p>Bonapartą. tu pan Hrabia z rzadka ciche szmery a brano z boru i Waszeć z Podkomorzym przy zachodzie wszystko porzucane niedbale i w pogody lilia jeziór skroń ucałowawszy, uprzejmie pozdrowił. A zatem. tu mieszkał? Stary żołnierz, stał w bitwie, gdzie panieńskim rumieńcem dzięcielina pała a brano z nieba spadała w pomroku. Wprawdzie zdała się pan Sędzia w lisa, tak nie rzuca w porządku. naprzód dzieci mało wdawał się ukłoni i czytając, z których nie śmieli. I bór czernił się pan rejent Bolesta, zwano go powitać.</p>')
            ");
            
            // Contact - EN
            $core->db()->pdo()->exec("INSERT INTO `pages` (`title`, `slug`, `desc`, `lang`, `template`, `date`, `content`)
                VALUES ('Contact', 'contact', '', 'en_english', 'index.html', datetime('now'),
                '<p>Want to get in touch with me? Fill out the form below to send me a message and I will try to get back to you within 24 hours!</p>
                {\$contact.form}')
            ");
            
            // Contact - PL
            $core->db()->pdo()->exec("INSERT INTO `pages` (`title`, `slug`, `desc`, `lang`, `template`, `date`, `content`)
                VALUES ('Kontakt', 'contact', '', 'pl_polski', 'index.html', datetime('now'),
                '<p>Chcesz się ze mną skontaktować? Wypełnij poniższy formularz, aby wysłać mi wiadomość, a ja postaram się odpisać w ciągu 24 godzin!</p>
                {\$contact.form}')
            ");
            
            // 404 - EN
            $core->db()->pdo()->exec("INSERT INTO `pages` (`title`, `slug`, `desc`, `lang`, `template`, `date`, `content`)
                VALUES ('404', '404', 'Not found', 'en_english', 'index.html', datetime('now'),
                '<p>Sorry, page does not exist.</p>')
            ");
            
            // 404 -PL
            $core->db()->pdo()->exec("INSERT INTO `pages` (`title`, `slug`, `desc`, `lang`, `template`, `date`, `content`)
                VALUES ('404', '404', 'Not found', 'pl_polski', 'index.html', datetime('now'),
                '<p>Niestety taka strona nie istnieje.</p>')
            ");

            if(!is_dir(UPLOADS."/pages"))
                mkdir(UPLOADS."/pages", 0777);
        },
        'uninstall'     =>  function() use($core)
        {
            $core->db()->pdo()->exec("DROP TABLE `pages`");
            deleteDir(UPLOADS."/pages");
        }
    ];