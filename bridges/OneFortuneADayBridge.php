<?php

class OneFortuneADayBridge extends BridgeAbstract
{
    const NAME = 'One Fortune a Day';
    const URI = 'https://github.com/fulmeek';
    const DESCRIPTION = 'Get a fortune quote every single day.';
    const MAINTAINER = 'fulmeek';
    const PARAMETERS = [[
        'time' => [
            'name'      => 'Time in UTC',
            'type'      => 'list',
            'values'    => [
                '0:00'  => 0,
                '1:00'  => 1,
                '2:00'  => 2,
                '3:00'  => 3,
                '4:00'  => 4,
                '5:00'  => 5,
                '6:00'  => 6,
                '7:00'  => 7,
                '8:00'  => 8,
                '9:00'  => 9,
                '10:00' => 10,
                '11:00' => 11,
                '12:00' => 12,
                '13:00' => 13,
                '14:00' => 14,
                '15:00' => 15,
                '16:00' => 16,
                '17:00' => 17,
                '18:00' => 18,
                '19:00' => 19,
                '20:00' => 20,
                '21:00' => 21,
                '22:00' => 22,
                '23:00' => 23,
            ],
            'defaultValue' => 5
        ],
        'lucky' => [
            'name' => 'Lucky number (optional)',
            'type' => 'text'
        ]
    ]];

    const LIMIT_ITEMS = 7;
    const DAY_SECS = 86400;

    public function getDescription()
    {
        return self::DESCRIPTION . '<br/>Set a lucky number to get your personal quotes, like ' . mt_rand();
    }

    public function collectData()
    {
        $time = gmmktime((int)$this->getInput('time'), 0, 0);
        if ($time > time()) {
            $time -= self::DAY_SECS;
        }

        for ($i = self::LIMIT_ITEMS; $i > 0; --$i) {
            $seed = gmdate('Ymd', $time) . $this->getInput('lucky');
            $quote = $this->getQuote($seed);

            $item['title']      = strftime('%A, %x', $time);
            $item['content']    = htmlentities($quote, ENT_QUOTES, 'UTF-8');
            $item['timestamp']  = $time;
            $item['uid']        = hash('sha1', $seed);

            $this->items[] = $item;

            $time -= self::DAY_SECS;
        }
    }

    private function getQuote($seed)
    {
        $quotes = explode(
            '//',
            <<<QUOTES
People are naturally attracted to you.
//You learn from your mistakes... You will learn a lot today.
//If you have something good in your life, don't let it go!
//What ever you're goal is in life, embrace it visualize it, and for it will be
yours.
//Your shoes will make you happy today.
//You cannot love life until you live the life you love.
//Be on the lookout for coming events; They cast their shadows beforehand.
//Land is always on the mind of a flying bird.
//The man or woman you desire feels the same about you.
//Meeting adversity well is the source of your strength.
//A dream you have will come true.
//Our deeds determine us, as much as we determine our deeds.
//Never give up. You're not a failure if you don't give up.
//You will become great if you believe in yourself.
//There is no greater pleasure than seeing your loved ones prosper.
//You will marry your lover.
//A very attractive person has a message for you.
//You already know the answer to the questions lingering inside your head.
//It is now, and in this world, that we must live.
//You must try, or hate yourself for not trying.
//You can make your own happiness.
//The greatest risk is not taking one.
//The love of your life is stepping into your planet this summer.
//Love can last a lifetime, if you want it to.
//Adversity is the parent of virtue.
//Serious trouble will bypass you.
//A short stranger will soon enter your life with blessings to share.
//Now is the time to try something new.
//Wealth awaits you very soon.
//If you feel you are right, stand firmly by your convictions.
//If winter comes, can spring be far behind?
//Keep your eye out for someone special.
//You are very talented in many ways.
//A stranger, is a friend you have not spoken to yet.
//A new voyage will fill your life with untold memories.
//You will travel to many exotic places in your lifetime.
//Your ability for accomplishment will follow with success.
//Nothing astonishes men so much as common sense and plain dealing.
//Its amazing how much good you can do if you dont care who gets the credit.
//Everyone agrees. You are the best.
//LIFE CONSIST NOT IN HOLDING GOOD CARDS, BUT IN PLAYING THOSE YOU HOLD WELL.
//Jealousy doesn't open doors, it closes them!
//It's better to be alone sometimes.
//When fear hurts you, conquer it and defeat it!
//Let the deeds speak.
//You will be called in to fulfill a position of high honor and responsibility.
//The man on the top of the mountain did not fall there.
//You will conquer obstacles to achieve success.
//Joys are often the shadows, cast by sorrows.
//Fortune favors the brave.
//An upward movement initiated in time can counteract fate.
//A journey of a thousand miles begins with a single step.
//Sometimes you just need to lay on the floor.
//Never give up. Always find a reason to keep trying.
//If you have something worth fighting for, then fight for it.
//Stop wishing. Start doing.
//Accept your past without regrets. Handle your present with confidence. Face
your future without fear.
//Stay true to those who would do the same for you.
//Ask yourself if what you are doing today is getting you closer to where you
want to be tomorrow.
//Happiness is an activity.
//Help is always needed but not always appreciated. Stay true to your heart and
help those in need weather they appreciate it or not.
//Hone your competitive instincts.
//Finish your work on hand don't be greedy.
//For success today, look first to yourself.
//Your fortune is as sweet as a cookie.
//Integrity is the essence of everything successful.
//If you're happy, you're successful.
//You will always be surrounded by true friends
//Believing that you are beautiful will make you appear beautiful to others
around you.
//Happinees comes from a good life.
//Before trying to please others think of what makes you happy.
//When hungry, order more Chinese food.
//Your golden opportunity is coming shortly.
//For hate is never conquered by hate. Hate is conquered by love .
//You will make many changes before settling down happily.
//A man is born to live and not prepare to live.
//You cannot become rich except by enriching others.
//Don't pursue happiness - create it.
//You will be successful in love.
//All your fingers can't be of the same length.
//Wise sayings often fall on barren ground, but a kind word is never thrown away.
//A lifetime of happiness is in store for you.
//It is very possible that you will achieve greatness in your lifetime.
//Be tactful; overlook your own opportunity.
//You are the controller of your destiny.
//Everything happens for a reson.
//How can you have a beutiful ending without making beautiful mistakes.
//You can open doors with your charm and patience.
//Welcome the change coming into your life.
//There will be a happy romance for you shortly.
//Your fondest dream will come true within this year.
//You have a deep interest in all that is artistic.
//Your emotional nature is strong and sensitive.
//A letter of great importance may reach you any day now.
//Good health will be yours for a long time.
//You will become better acquainted with a coworker.
//To be old and wise, you must first be young and stupid.
//Failure is only the opportunity to begin again more intelligently.
//Integrity is doing the right thing, even if nobody is watching.
//Conquer your fears or they will conquer you.
//You are a lover of words; One day you will write a book.
//In this life it is not what we take up, but what we give up, that makes us
rich.
//Fear can keep us up all night long, but faith makes one fine pillow.
//Seek out the significance of your problem at this time. Try to understand.
//Never upset the driver of the car you're in; they're the master of your
destiny until you get home.
//He who slithers among the ground is not always a foe.
//You learn from your mistakes, you will learn a lot today.
//You only need look to your own reflection for inspiration. Because you are
Beautiful!
//You are not judged by your efforts you put in; you are judged on your
performance.
//Rivers need springs.
//Good news from afar may bring you a welcome visitor.
//When all else seems to fail, smile for today and just love someone.
//Patience is a virtue, unless its against a brick wall.
//When you look down, all you see is dirt, so keep looking up.
//If you are afraid to shake the dice, you will never throw a six.
//Even if the person who appears most wrong, is also quite often right.
//A single conversation with a wise man is better than ten years of study.
//Happiness is often a rebound from hard work.
//The world may be your oyster, but that doesn't mean you'll get it's pearl.
//Your life will be filled with magical moments.
//You're true love will show himself to you under the moonlight.
//Do not follow where the path may lead. Go where there is no path...and leave a
trail
//Do not fear what you don't know
//The object of your desire comes closer.
//You have a flair for adding a fanciful dimension to any story.
//If you wish to know the mind of a man, listen to his words
//The most useless energy is trying to change what and who God so carefully
created.
//Do not be covered in sadness or be fooled in happiness they both must exist
//You will have unexpected great good luck.
//You will have a pleasant surprise
//All progress occurs because people dare to be different.
//Your ability for accomplishment will be followed by success.
//The world is always ready to receive talent with open arms.
//Things may come to those who wait, but only the things left by those who
hustle.
//We can't help everyone. But everyone can help someone.
//Every day is a new day. But tomorrow is never promised.
//Express yourself: Don't hold back!
//It is not necessary to show others you have change; the change will be obvious.
//You have a deep appreciation of the arts and music.
//If your desires are not extravagant, they will be rewarded.
//You try hard, never to fail. You don't, never to win.
//Never give up on someone that you don't go a day without thinking about.
//It never pays to kick a skunk.
//In case of fire, keep calm, pay bill and run.
//Next full moon brings an enchanting evening.
//Not all closed eye is sleeping nor open eye is seeing.
//Impossible is a word only to be found in the dictionary of fools.
//You will soon witness a miracle.
//The time is alway right to do what is right.
//Love is as necessary to human beings as food and shelter.
//You will make heads turn.
//You are extremely loved. Don't worry :)
//If you are never patient, you will never get anything done. If you believe you
can do it, you will be rewarded with success.
//You will soon embark on a business venture.
//You believe in the goodness of man kind.
//You will have a long and wealthy life.
//You will take a pleasant journey to a place far away.
//You are a person of culture.
//Keep it simple. The more you say, the less people remember.
//Life is like a dogsled team. If you ain't the lead dog, the scenery never
changes.
//Prosperity makes friends and adversity tries them.
//Nothing seems impossible to you.
//Patience is bitter, but its fruit is sweet.
//The only certainty is that nothing is certain.
//Success is the sum of my unique visions realized by the sweat of perseverance.
//When you expect your opponent to yield, you also should avoid hurting him.
//Human evolution: “wider freeway but narrower viewpoints.
//Intelligence is the door to freedom and alert attention is the mother of
intelligence.
//Back away from individuals who are impulsive.
//Enjoyed the meal? Buy one to go too.
//You believe in the goodness of mankind.
//A big fortune will descend upon you this year.
//Now these three remain, faith, hope, and love. The greatest of these is love.
//For success today look first to yourself.
//Determination is the wake-up call to the human will.
//There are no limitations to the mind except those we aknowledge.
//A merry heart does good like a medicine.
//Whenever possible, keep it simple.
//Your dearest wish will come true.
//Poverty is no disgrace.
//If you don’t do it excellently, don’t do it at all.
//You have an unusual equipment for success, use it properly.
//Emotion is energy in motion.
//You will soon be honored by someone you respect.
//Punctuality is the politeness of kings and the duty of gentle people
everywhere.
//Your happiness is intertwined with your outlook on life.
//Elegant surroundings will soon be yours.
//If you feel you are right, stand firmly by your convictions.
//Your smile brings happiness to everyone you meet.
//Instead of worrying and agonizing, move ahead constructively.
//Do you believe? Endurance and persistence will be rewarded.
//A new business venture is on the horizon.
//Never underestimate the power of the human touch.
//Hold on to the past but eventually, let the times go and keep the memories
into the present.
//Truth is an unpopular subject. Because it is unquestionably correct.
//The most important thing in communication is to hear what isn’t being said.
//You are broad minded and socially active.
//Your dearest dream is coming true. God looks after you especially.
//You will recieve some high prize or award.
//Your present question marks are going to succeed.
//You have a fine capacity for the enjoyment of life.
//You will live long and enjoy life.
//An admirer is concealing his/her affection for you.
//A wish is what makes life happen when you dream of rose petals.
//Love can turn cottage into a golden palace.
//Lend your money and lose your freind.
//You will kiss your crush ohhh lalahh
//You will be rewarded for being a good listener in the next week.
//If you never give up on love, It will never give up on you.
//Unleash your life force.
//Your wish will come true.
//There is a prospect of a thrilling time ahead for you.
//No distance is too far, if two hearts are tied together.
//Land is always in the mind of the flying birds.
//Try? No! Do or do not, there is no try.
//Do not worry, you will have great peace.
//It's about time you asked that special someone on a date.
//You create your own stage ... the audience is waiting.
//It is never too late. Just as it is never too early.
//Discover the power within yourself.
//Good things take time.
//Stop thinking about the road not taken and pave over the one you did.
//Put your unhappiness aside. Life is beautiful, be happy.
//You can still love what you can not have in life.
//Make a wise choice everyday.
//Circumstance does not make the man; it reveals him to himself.
//The man who waits till tomorrow, misses the opportunities of today.
//Life does not get better by chance. It gets better by change.
//If you never expect anything you can never be disappointed.
//People in your surroundings will be more cooperative than usual.
//True wisdom is found in happiness.
//Ones always regrets what could have done. Remember for next time.
//Follow your bliss and the Universe will open doors where there were once only
walls.
//Find a peaceful place where you can make plans for the future.
//All the water in the world can't sink a ship unless it gets inside.
//The earth is a school learn in it.
//In music, one must think with his heart and feel with his brain.
//If you speak honestly, everyone will listen.
//Ganerosity will repay itself sooner than you imagine.
//good things take time
//Do what is right, not what you should.
//To effect the quality of the day is no small achievement.
//Simplicity and clearity should be the theme in your dress.
//Virtuous find joy while Wrongdoers find grieve in their actions.
//Not all closed eye is sleeping, nor open eye is seeing.
//Bread today is better than cake tomorrow.
//In evrything there is a piece of truth.But a piece.
//A feeling is an idea with roots.
//Man is born to live and not prepare to live
//It's all right to have butterflies in your stomach. Just get them to fly in
formation.
//If you don t give something, you will not get anything
//The harder you try to not be like your parents, the more likely you will
become them
//Someday everything will all make perfect sense
//you will think for yourself when you stop letting others think for you
//Everything will be ok. Don't obsess. Time will prove you right, you must stay
where you are.
//Let's finish this up now, someone is waiting for you on that
//The finest men like the finest steels have been tempered in the hottest
furnace.
//A dream you have will come true
//The worst of friends may become the best of enemies, but you will always find
yourself hanging on.
//I think, you ate your fortune while you were eating your cookie
//If u love someone keep fighting for them
//Do what you want, when you want, and you will be rewarded
//Let your fantasies unwind...
//The cooler you think you are the dumber you look
//Expect great things and great things will come
//The Wheel of Good Fortune is finally turning in your direction!
//Don't lead if you won't lead.
//You will always be successful in your professional career
//Share your hapiness with others today.
//It's up to you to clearify.
//Your future will be happy and productive.
//Seize every second of your life and savor it.
//Those who walk in other's tracks leave no footprints.
//Failure is the mother of all success.
//Difficulty at the beginning useually means ease at the end.
//Do not seek so much to find the answer as much as to understand the question
better.
//Your way of doing what other people do their way is what makes you special.
//A beautiful, smart, and loving person will be coming into your life.
//Friendship is an ocean that you cannot see bottom.
//Your life does not get better by chance, it gets better by change.
//Our duty,as men and women,is to proceed as if limits to our ability did not
exist.
//A pleasant expeience is ahead:don't pass it by.
//Our perception and attitude toward any situation will determine the outcome
//They say you are stubborn; you call it persistence.
//Two small jumps are sometimes better than one big leap.
//A new wardrobe brings great joy and change to your life.
//The cure for grief is motion.
//It's a good thing that life is not as serious as it seems to the waiter
//I hear and I forget. I see and I remember. I do and I understand.
//I have a dream....Time to go to bed.
//Ideas you believe are absurd ultimately lead to success!
//A human being is a deciding being.
//Today is an ideal time to water your parsonal garden.
//Some men dream of fortunes, others dream of cookies.
//Things are never quite the way they seem.
//the project on your mind will soon gain momentum
//YOUR FAILURES WILL LEAD YOU TO YOUR SUCCESS.
//IN ORDER TO GET THE RAINBOW, YOU MUST ENDURE THE RAIN.
//Beauty is simply beauty. originality is magical.
//Your dream will come true when you least expect it.
//Let not your hand be stretched out to receive and shut when you should repay.
//Don't worry, half the people you know are below average.
//Vision is the art of seeing what is invisible to others.
//You don't need talent to gain experience.
//A focused mind is one of the most powerful forces in the universe.
//Today you shed your last tear. Tomorrow fortune knocks at your door.
//Be patient! The Great Wall didn't got build in one day.
//Think you can. Think you can't. Either way, you'll be right.
//Wisdom is on her way to you.
//Digital circuits are made from analog parts.
//If you eat a box of fortune cookies, anything is possible.
//The best is yet to come.
//I'm with you.
//Be direct,usually one can accomplish more that way.
//A single kind work will keep one warm for years.
//Ask a friend to join you on your next voyage.
//In God we trust.
//Love is free. Lust will cost you everything you have.
//Stop searching forever, happiness is just next to you.
//You don't need the answers to all of life's questions. Just ask your father
what to do.
//Jealousy is a useless emotion.
//You are not a ghost.
//There is someone rather annoying in your life that you need to listen to.
//You will plant the smallest seed and it will become the greatest and most
mighty tree in the world.
//The dream you've been dreaming all your life isn't worth it. Find a new dream,
and once you're sure you've found it, fight for it.
//See if you can learn anything from the children.
//It's Never Too Late For Good Things To Happen!
//A clear conscience is usually the sign of a bad memory.
//Aim high, time flies.
//One is not sleeping, does not mean they are awake.
//A great pleasure in life is doing what others say you can't.
//Isn't there something else you should be working on right now?
//Your father still loves and is in always with you. Remember that.
//Before you can be reborn you must die.
//It better to be the hammer than the nail.
//You are admired by everyone for your talent and ability.
//Save the whales. Collect the whole set.
//You will soon discover a major truth about the one you love most.
//Your life will prosper only if you acknowledge your faults and work to reduce
them.
//Pray to God, but row towards shore.
//You will soon witness a miracle.
//The early bird gets the worm, but the second mouse gets the cheese
//Help, I'm being held prisoner in a Chinese cookie factory.
//Alas! The onion you are eating is someone else’s water lily.
//You are a persoon with a good sense of justice, now it's time to act like it.
//You create enthusiasm around you.
//There are big changes ahead for you. They will be good ones!
//You will have many happy days soon.
//Out of confusion comes new patterns.
//If you love someone enough and they break your heart, you can't stop yourself
from still loving them again even after all that pain.
//Look right...Now look left...Now look forward (do this really fast) do you
feel any different? good you should feel dizzy.
//Live like you are on the bottom, even if you are on the top.
//You will soon emerge victorious from the maze you've been traveling in.
//Do not judge a book by it's color.
//Everything will come your way.
//There is a time to be practical now.
//Bend the rod while it is still hot.
//Darkness is only succesful when there is no light. Don't forget about light!
//Acting is not lying. It is findind someone hiding inside you and letting that
person run free.
//You will be forced to face fear, but if you do not run, fear will be afraid of
you.
//You are thinking about doing something. Don't do it, it won't help anything.
//Your worst enemy has a crush on you!
//Love Conquers all.
//The phrase is follow your dreams. Not dream period.
//stop nagging to your partner and take it day by day.
//Do not think that me or my brothers have supreme control over what will happen
to you.
//Bad luck and misfortune will follow you all your days.
//Remember the fate of the early Worm.
//Begin your life anew with strength, grace and wonder.
//Be a good friend and a fair enemy.
//What goes around comes around.
//Bad luck and misfortune will infest your pathetic soul for all eternity.
//The best prophet of the future is the past
//Movies have pause buttons, friends do not
//Use the force.
//Trust your intuition.
//Encourage your peers.
//Let your imagination wander.
//Your pain is the breaking of the shell that encloses your understanding.
//Patience is key, a wait short or long will have its reward.
//Tell them before it's too late...
//A bird in the hand is worth three in the bush!!
//Be assertive when decisive action is needed.
//To determine whether someone is beautiful is not by looking at his/her
appearance, but his/her heart.
//Hope brings about a better future
//While you have this day, fill it with life. While you're in this moment, give
it your own special meaning and purpose and joy.
//Even though it will often be difficult and complicated, you know you have what
it takes to get it done.
//You can choose, right now and in every moment, to put your powerful and
effective abilities to purposeful use. There is always something you can do, no
matter what the situation may be, that will move your life forward.
//IT IS NOT GOOD TO BE A USER BLESSINGS COME FROM BEING A GIVER NOT A TAKER.
//Cookie says, You crack me up
//You will prosper in the field of wacky inventions.
//Your tongue is your ambassador.
//The cure for grief is movement.
//Love Is At Your Hands Be Glad And Hold On To It.
//You are often asked if it is in yet.
//Life to you is a bold and dashing responsibility.
//Patience is a key to joy.
//A bargain is something you don't need at a price you can't resist.
//Today is going to be a disasterous day, be prepared!
//Stay to your inner-self, you will benefit in many ways.
//Rarely do great beauty and great virtue dwell together as they do in you.
//You are talented in many ways.
//You are the master of every situation.
//Your problem just got bigger. Think, what have you done.
//If your cookie still in one piece, buy lotto.
//Go with the flow will make your transition ever so much easier.
//Tomorrow Morning,Take a Left Turn As Soon As You Leave Home
//A metaphor could save your life.
//Don't wait for your ship to come in, swim out to it
//There are lessons to be learned by listening to others.
//If you want the rainbow, you have to tolerate the rain.
//Volition, Strength, Languages, Freedom and Power rests in you.
//TOO MANY PEOPLE VOLUNTEER TO CARRY THE STOOL WHEN ITS TIME TO MOVE THE PIANO
//It takes more than a good memory to have good memories.
//You are what you are; understand yourself before you react
//Word to the wise: Don't play leapfrog with a unicorn.........
//Forgive your enemies, but never forget them.
//Everything will now come your way
//Don't worry about the stock market. Invest in family.
//Your fortune is as sweet as a cookie.
//It is much easier to look for the bad, than it is to find the good
//If a person who has caused you pain and suffering has brought you, reconsider
that person's value in your life
//You are worth loving, you are also worth the effort it takes to love you
//Never trouble trouble till trouble troubles you.
//Get off to a new start - come out of your shell.
//Life is a dancefloor,you are the DJ!
//Cooperate with those who have both know how and integrith.
//Minor aches today are likely to pay off handsomely tomorrow.
//You are about to become $8.95 poorer. ($6.95 if you had the buffet)
//Your mouth may be moving, but nobody is listening.
//Focus in on the color yellow tomorrow for good luck!
//The problem with resisting temptation is that it may never come again.
//All your sorrows will vanish.
//About time I got out of that cookie.
//Love will lead the way.
//The ads revenge is massive success
//It is best to act with confidence, no matter how little right you have to it.
//Soon, a visitor shall delight you.
//What breaks in a moment may take years to mend.
//Someone stole your fortune and replaced it with this one. Your luck sucks.
Have a good day!
//Take control of your life rather than letting things happen just like that!
//You will be rewarded for your patience and understanding.
//You will achieve all your desires and pleasures.
//Never miss a chance to keep your mouth shut.
//Nothing Shows A Man's Character More Than What He Laughs At.
//Never regret anything that made you smile.
//Love Takes Pratice.
//Don't take yourself so seriously, no one else does.
//You've got what it takes, but it will take everything you've got!
//At this very moment you can change the rest of your life.
//Become who you are.
//All comes at the proper time to him who knows how to wait.
//The energy is within you. Money is Coming!
//The quotes that you do not understand, are not meant for you.
//You have an important new business development shaping up.
//if love someone a lot tell it before it's too late
//Birds are entangled by their feet and men by their tongues.
//Benefit by doing things that others give up on.
//Rest has a peaceful effect on your physical and emotional health.
//One of the best ways to persuade others is with your ears--by listening to
them.
//Plan your work and work your plan.
//Over self-confidence is equal to being blind.
//Those who bring sunshine to the lives of others cannot keep it from themselves.
//Love or money, or neither?
//Before the beginning of great brilliance, there must be chaos.
//Old friends make best friends.
//Stop searching forever. Happiness is just next to you.
//Accept something that you cannot change, and you will feel better.
//Kiss is not a kiss without the heart.
//Enhance your karma by engaging in various charitable activities.
//You will have good luck and overcome many hardships.
//You never hesitate to tackle the most difficult problems.
//Hope is like food. You will starve without it.
//WHEN FIRE AND WATER GO TO WAR WATER ALWAYS WINS.
//An angry man opens his mouth and shuts up his eyes.
//Make the system work for you, not the other way around.
//You will be hungry soon, order takeout now.
//Be prepared for extra energy.
//An unexpected relationship will become permanent.
//The love of your life is sitting across from you.
//Better be the head of a chicken than the tail of an ox.
//To forgive others one more time is to create one more blessing for yourself.
//Enjoy yourself while you can.
//The ultimate test of a relationship is to disagree but to hold hands.
//Excellence is the difference between what I do and what I am capable of.
//Do not let what you do not have prevent you from using what you do have.
//What ends on hope does not end at all.
//People enjoy having you around. Appreciate this.
//You are admired for your adventuous ways.
//It's never crowded along the extra mile
//You are blessed, today is the day to bless others.
//The Greatest War Sometimes Isn't On The Battlefield But Against Oneself.
//People in your background will be more co-operative than usual.
//A good way to stay healthy is to eat more Chinese food.
//Anyone who dares to be, can never be weak.
//Affirm it, visualize it, believe it, and it`will actualize itself.
//The measure of time to your next goal is the measure of your discipline.
//Help, I'm prisoner in a Chinese bakery!!!
//Take a minute and let it ride, then take a minute to let it breeze.
//We are here to love each other, serve each other and uplift each other.
//If everybody is a worm you should be a glow worm
//To affirm is to make firm.
//Remember this: duct tape can fix anything, so don't worry about messing things
up.
//You broke my cookie!
//Failure is not defeat until you stop trying.
//The days that make us happy make us wise.
//Men do not fail... they give up trying.
//Time may fly by. But Memories don't.
//You will win success in whatever you adopt.
//You will outdistance all your competitors.
//You have a great capability to break cookies - use it wisely!
//AT TIMES IT IS BETTER TO KNOW WHEN EXIT THAN ENTER
//Money will come to you when you are doing the right thing.
//When you get something for nothing, you just haven't been billed for it yet.
//You will discover your hidden talents.
//You'll advance for with your abilities.
//When you can't naturally feel upbeat it can sometimes help you to act as if
you did.
//You will overcome difficult times.
//Your problem just became your stepping stone. Catch the moment.
//I am a fortune. You just broke my little house. Where will i live now?
//The majority of the word can't is can.
//The secret of getting ahead is getting started.
//Be most affectionate today.
//Change your thoughts and you change the world.
//Sing and rejoice, fortune is smiling on you.
//All the preparation you've done will finally be paying off!
//A truly great person never puts away the simplicity of a child.
//Customer service is like taking a bath you have to keep doing it.
//The expanse of your intelligence is a void no universe could ever fill.
//Those grapes you cannot taste are always sour.
//An unexpected aquaintance will resurface.
//If you want the rainbow, then you have to tolerate the rain.
//You don't get harmony when everyone sings the same note.
//The race is not always to the swift, but to those who keep on running.
//The early bird gets the worm, but the second mouse gets the cheese.
//The best things in life aren't things.
//Don't bother looking for fault. The reward for finding it is low.
//Everything has beauty but not everyone sees it.
//Nothing is as good or bad as it appears.
//Never cut what you can untie.
//Meet your opponent half way. You need the exercise.
//Laughter is the shortest distance between two people.
//We cannot change the direction of the wind, but we can adjust our sails.
//We could learn a lot from crayons: Some of are sharp, some are pretty, some
have weird names, and all are different colors. But they all have to learn to
live in the same box.
//Use your instincts now.
//If you take a single step to your journey, you'll succeed; it's not best to
fail.
//In the eyes of lovers, everything is beautiful.
//Warning, do not eat your fortune.
//Demonstrate refinement in everything you do.
//Impossible standards just make life difficult.
//A different world cannot be build by indifferent people.
//Q. What is H2O? A. Caring, 2 parts Hug and 1 part Open-mind.
//All troubles you have can pass away very quickly.
//Integrity is the essense of everything successful.
//For true love? Send real roses preserved in 24kt gold!
//Sometimes the object of the journey is not the end, but the journey itself.
//Fear is just excitement in need of an attitude adjustment.
//The food here taste so good, even a cave man likes it.
//Perhaps you've been focusing too much on spending.
//Happiness isn't something you remember, it's something you experience.
//Oops... Wrong cookie.
//The dream is within you.
//Love is on its way.
//Be direct, usually one can accomplish more that way.
//Use your talents. That's what they are intended for.
//The troubles you have now will pass away quickly.
//See the light at the end of the tunnel.
//Your dream will come true when you least expect it.
//Don't 'face' reality, let it be the place from which you leap.
//Fortune smiles upon you today.
//Believing is doing.
//Your dynamic eyes have attracted a secret admirer.
//You know where you are going and how to get there.
//Go confidently in the direction of your dreams.
//Your ability to pick a winner will bring you success.
//Humor usually works at the moment of awkwardness.
//A good time to finish up old tasks.
//Stop procrastinating - starting tomorrow
//Enthusiastic leadership gets you a promotion when you least expect it.
//You love Chinese food.
//You are far more influential than you think.
//Adjust finances, make budgets, to improve your standing.
//Happiness is not the absence of conflict, but the ability to cope with it.
//An understanding heart warms all that are graced with it's presense.
//Your co-workers take pleasure in your great sense of creativity.
//You are one of the people who goes places in life.
//Others enjoy your company.
//When in doubt, let your instincts guide you.
//A cheerful message is on its way to you.
//A pleasant surprise is in store for you tonight.
//you cant go down the right path with out first discovering the path to go down
//To courageously shoulder the responsibility of one's mistake is character.
//The joyful energy of the day will have a positive affect on you.
//You have a strong desire for a home and your family interests come first.
//Dogs have owners, cats have staff.
//Be patient: in time, even an egg will walk.
//You are not a person who can be ignored.
//You always know the right times to be assertive or to simply wait.
//Reading to the mind is what exercise is to the body.
//Eat something you never tried before.
//Your life becomes more and more of an adventure!
//You need to live authentically, and you can't ignore that.
//Make all you can, save all you can, give all you can.
//A well-aimed spear is worth three.
//To build a better world, start in your community.
//When you can't naturally feel upbeat, it can sometimes help to act a if you
did.
//May you have great luck.
//A kind word will keep someone warm for years.
//Nothing in the world is accomplished without passion.
//Human invented language to satisfy the need to complain.
//Accept what comes to you each day.
//A small lucky package is on its way to you soon.
//In human endeavor, chance favors the prepared mind.
//Do not upset the penguin today.
//Don't cry.
//The best way to give credit is to give it away.
//Anything you do, do it well. The last thing you want is to be sorry for what
you didn't do.
//It takes more then good memory to have good memories.
//Grant yourself a wish this year only you can do it.
//love thy neighbour, just don't get caught
//You will be selected for a promotion because of your accomplishments.
//There are many new opportunities that are being presented to you.
//You will inherit a large sum of money.
//You will recieve a gift from someone that cares about you.
//You are not illiterate.
//Love because it is the only true adventure.
//You are contemplating some action which will bring credit upon you
//Keep true to the dreams of your youth.
//Treasure what you have.
//The greatest precept is continual awareness.
//A new friend helps you break out of an old routine.
//I have a dream.... Time to go to bed.
//Your skill will accomplish what the force of many cannot.
//You will soon be surrounded by good friends and laughter.
//The best is yet to come.
//It is better to be the hammer then the anvil.
//He who climbs a ladder must begin at the first step.
//Action speaks nothing, without the Motive.
//Give yourself some peace and quiet for at least a few hours.
//Live each day well and wisely
//Old dreams never die they just get filed away.
//You can fix it with a little extra energy and a positive attitude.
//Life is a verb
//A man without aim is like a clock without hands, as useless if it turns as if
it stands.
//Many folks are about as happy as they make up their minds to be.
//It's kind of fun to do the impossible
//Wow! A secret message from you teeth!
//You should be able to make money and hold on to it.
//The human spirit is stronger than anything that can happen to it.
//Your succeess will astonish everyone.
//It is better to have a hen tomorrow than an egg today.
//Judge each day not by the harvest you reap but by the seeds you plant.
//You like Chinese food.
//Your hard work will get payoff today.
//Today is the tomorrow we worried about yesterday
//There are no shortcuts to any place worth going
//No matter what your past has been, you have a spotless future.
//Your secret desire to completely change your life will manifest.
//Soon you will be sitting on top of the world.
//You are never selfish with your advice or your help.
//A thrilling time is in store for you.
//It's tough to be fascinating.
//Soon life will become more interesting
//Luck sometimes visits a fool, but it never sits down with him.
//Keep your plans secret for now.
//Aren't you glad you just had a great meal?
//Traveling this year will bring your life into greater perspective.
//Only talent people get help from others.
//Constant grinding can turn an iron nod into a needle.
//You will be successful in your work
//you will spend old age in confort and material wealth
//When you're about to turn your heart into a stone remember: you do not walk
alone.
//I am a bad luck person since I was born
//You are vigorous in words and action.
//The one who snores will always fall asleep first.
//An alien of some sort will be appearing to you shortly!
//Rest is a good thing, but boredom is its brother.
//Do not be overly judgemental of your loved one's intentions or actions.
//Think of how you can assist on a problem, not who to blame.
//The life of every woman or man - the heart of it - is pure and holy joy.
//Take it easy
//Trust your intuition. The universe is guiding your life.
//Use your head, but live in your heart.
//Don't find fault, find a remedy
//It may be those who do most, dream most
//Your passions sweep you away.
//Listen to yourself more often
//Think of mother's exhortations more.
//The gambler is like the fisherman both have beginners luck.
//You are given the chance to take part in an exciting adventure.
//The simplest answer is to act.
//You will always be surrounded by true friends.
//Keep your feet on the ground even though friends flatter you.
//You are the man of righteousness and integrity.
//He who seeks will find.
//The smart thing to do is to begin trusting your intuitions.
//Your many hidden talents will become obvious to those around you.
//Pick a path with heart.
//The human spirit is stronger then anything that can happen to it.
//It takes more than good memory to have good memories.
//Face facts with dignity.
//Be calm when confronting an emergency crisis.
//Do you believe? Endurance and persistence will be rewarded.
//A new wardrobe brings great joy and change in your life.
//Everyone agrees you are the best.
//A new outlook brightens your image and brings new friends.
//Everything will now come your way.
//You will be called to fill a position of high honor and responsibility.
//The eyes believe themselves; the ears believe other people.
//Good beginning is half done.
//Some pursue happiness; you create it.
//It's the worst of times, you need to summon your optimism.
//You are cautious in showing your true self to others.
//Your ability to accomplish tasks will follow with success.
//We all have extraordinary coded within us, waiting to be released.
//You will have a bright future.
//Compassion is a way of being.
//You will always have good luck in your personal affairs.
//The pleasure of what we enjoy is lost by wanting more
//Did you remember to order your take out also?
//Perhaps you've been focusing too much on that one thing..
//Right now there's an energy pushing you in a new direction.
//Everybody feels lucky for having you as a friend.
//When the moment comes, take the top one.
//Sometimes travel to new places leads to great transformation.
//There is always a way - if you are committed.
//Life is too short to waste time hating anyone.
//All the world may not love a lover but they will be watching him.
//Don't just spend time, invest it.
//Life always gets harder near the summit.
//Take the chance while you still have the choice.
//It is much easier to be cirtical than to be correct.
//Enjoy life! It is better to be happy than wise.
//To make the cart go, you must grease the wheels.
//You are contemplating some action which will bring credit upon you.
//Before you wonder Am I doing things right, ask Am I doing the right things?
//You may be disappointed if you fail, but you are doomed if you don't try.
//You will always get what you want through your charm and personality.
//The big issues are work, career, or status right now.
//Your emotional currents are flowing powerfully now.
//Any decision you have to make tomorrow is a good decsion.
//Consume less. Share more. Enjoy life.
//The secret of staying young is good health and lying about your age.
//Spring has sprung. Life is blooming.
//Go ask your mom.
//The possibility of a career change is near.
//The important thing is to never stop questioning.
//Compassion will cure more then condemnation.
//Excuses are easy to manufacture, and hard to sell.
//Put your mind into planning today. Look into the future.
//Listen to life, and you will hear the voice of life crying, Be!
//Broke is only temporaryl poor is a state of mind.
//Here we go. Moo Shu Cereal for breakfast with duck sauce.
//Teamwork: the fuel that allows common people attain uncommon results.
//Hard words break no bones, fine words butter no parsnips.
//We cannot direct the wind but we can adjust the sails.
//You are offered the dream of a lifetime. Say yes!
//Working out the kinks today will make for a better tomorrow.
//You have a curious smile and a mysterious nature.
//Questions provide the key to unlocking our unlimited potential.
//You will enjoy razon-sharp spiritual vision today.
//The wise are aware of their treasure, while fools follow their vanity
//Well-arranged time is the surest sign of a well-arranged mind.
//Never bring unhappy feelings into your home.
//This is really a lovely day. Congratulations!
//Bad luck and ill misfortune will infest your pathetic soul for all eternity.
//A golden egg of opportunity falls into your lap this month.
//You are very grateful for the small pleasures of life.
//today you should be a passenger. Stay close to a driver for a day.
//For hate is never conquered by hate. Hate is conquered by love.
//Service is the rent we pay for the privilege of living on this planet.
//Good clothes open many doors. Go shopping.
//The leader seeks to communicate his vision to his followers.
//Great works are performed not by strength, but by perseverance.
//People who are late are often happier than those who have to wait for them
//Present your best ideas today to an eager and welcoming audience.
//Friends long absent are coming back to you.
//The time is right to make new friends.
//Life to you is a dashing and bold adventure
//You may be hungry soon: order a takeout now.
//Do not hesitate to look for help, an extra hand should always be welcomed.
//How can you have a beautiful ending without making beautiful mistakes?
//Humor is an affirmation of dignity
//He who climbs a ladder must begin at the first step
//What's vice today may be virtue tomorow.
//You have an unusually magnetic personality.
//You will travel to many places.
//Accept yourself
//Be a generous friend and a fair enemy
//Never quit!
//Old friends, old wines and old gold are best
//If your desires are not extravagant, they will be granted
//Every Friend Joys in your Success
//You should be able to undertake and complete anything
//You will enjoy good health, you will be surrounded by luxury
//You are a person of strong sense of duty
//Dream lofty dreams, and as you dream, so shall you become.
//You have a quiet and unobtrusive nature.
//Great thoughts come from the heart.
//You love peace
//Judge not according to the appearance.
//One who admires you greatly is hidden before your eyes.
//Traveling more often is important for your health and happiness.
//You will be sharing great news with all people you love
//You have a reputation for being straightforward and honest.
//You are always welcome in any gathering.
//You will be traveling and coming into a fortune.
//Open up your heart - it can always be closed again.
//Being happy is not always being perfect.
//Next time you have the opportunity, go on a rollercoaster.
//Try everything once, even the things you don't think you will like.
//Life is too short to hold grudges.
//Dream your dream and your dream will dream of you.
//Being alone and being lonely are two different things.
//Don't worry about things in the past, there is nothing you can do about them
now. Don't worry about things that are happening now, make the best of a bad
situation. Don't worry about things in the future, they may never happen.
//Tomorrow, take a moment to do something just for yourself.
//Someone close to you is waiting for you to call.
//A virtual fortune cookie will not satisfy your hunger like that of a home made
one.
//Smile. Tomorrow is another day.
//You can never been certain of success, but you can be certain of failure if
you never try.
//It takes ten times as many muscles to frown as it does to smile.
//Shoot for the moon! If you miss you will still be amongst the stars.
//Keep your eyes open. You never know what you might see.
//Tell them what you really think. Otherwise, nothing will change.
//Let your heart make your decisions - it does not get as confused as your head.
//Working hard will make you live a happy life.
//A pleasant surprise is waiting for you.
QUOTES
        );

        $i = round(fmod(hexdec(hash('crc32', $seed)), count($quotes)), 0);
        return trim(str_replace(["\r\n", "\n", "\r"], ' ', $quotes[$i]));
    }
}
