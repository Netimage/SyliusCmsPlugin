<?php

/*
 * This file has been created by developers from BitBag.
 * Feel free to contact us once you face any issues or want to start
 * another great project.
 * You can find more information about us on https://bitbag.shop and write us
 * an email on mikolaj.krol@bitbag.pl.
 */

declare(strict_types=1);

namespace BitBag\SyliusCmsPlugin\Fixture\Factory;

use BitBag\SyliusCmsPlugin\Assigner\ChannelsAssignerInterface;
use BitBag\SyliusCmsPlugin\Assigner\ProductsAssignerInterface;
use BitBag\SyliusCmsPlugin\Assigner\SectionsAssignerInterface;
use BitBag\SyliusCmsPlugin\Assigner\TaxonsAssignerInterface;
use BitBag\SyliusCmsPlugin\Entity\BlockInterface;
use BitBag\SyliusCmsPlugin\Entity\BlockTranslationInterface;
use BitBag\SyliusCmsPlugin\Repository\BlockRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

final class BlockFixtureFactory implements FixtureFactoryInterface
{
    /** @var FactoryInterface */
    private $blockFactory;

    /** @var FactoryInterface */
    private $blockTranslationFactory;

    /** @var BlockRepositoryInterface */
    private $blockRepository;

    /** @var ProductsAssignerInterface */
    private $productsAssigner;

    /** @var TaxonsAssignerInterface */
    private $taxonsAssigner;

    /** @var SectionsAssignerInterface */
    private $sectionsAssigner;

    /** @var ChannelsAssignerInterface */
    private $channelAssigner;

    public function __construct(
        FactoryInterface $blockFactory,
        FactoryInterface $blockTranslationFactory,
        BlockRepositoryInterface $blockRepository,
        ProductsAssignerInterface $productsAssigner,
        TaxonsAssignerInterface $taxonsAssigner,
        SectionsAssignerInterface $sectionsAssigner,
        ChannelsAssignerInterface $channelAssigner
    ) {
        $this->blockFactory = $blockFactory;
        $this->blockTranslationFactory = $blockTranslationFactory;
        $this->blockRepository = $blockRepository;
        $this->productsAssigner = $productsAssigner;
        $this->taxonsAssigner = $taxonsAssigner;
        $this->sectionsAssigner = $sectionsAssigner;
        $this->channelAssigner = $channelAssigner;
    }

    public function load(array $data): void
    {
        foreach ($data as $code => $fields) {
            if (
                true === $fields['remove_existing'] &&
                null !== $block = $this->blockRepository->findOneBy(['code' => $code])
            ) {
                $this->blockRepository->remove($block);
            }

            if (null !== $fields['number']) {
                for ($i = 0; $i < $fields['number']; ++$i) {
                    $this->createBlock(md5(uniqid()), $fields);
                }
            } else {
                $this->createBlock($code, $fields);
            }
        }
    }

    private function createBlock(string $code, array $blockData): void
    {
        /** @var BlockInterface $block */
        $block = $this->blockFactory->createNew();

        $this->sectionsAssigner->assign($block, $blockData['sections']);
        $this->productsAssigner->assign($block, $blockData['products']);
        $this->taxonsAssigner->assign($block, $blockData['taxons']);
        $this->channelAssigner->assign($block, $blockData['channels']);

        $block->setCode($code);
        $block->setEnabled($blockData['enabled']);

        foreach ($blockData['translations'] as $localeCode => $translation) {
            /** @var BlockTranslationInterface $blockTranslation */
            $blockTranslation = $this->blockTranslationFactory->createNew();

            $blockTranslation->setLocale($localeCode);
            $blockTranslation->setName($translation['name']);
            $blockTranslation->setContent($translation['content']);
            $blockTranslation->setLink($translation['link']);
            $block->addTranslation($blockTranslation);
        }

        $this->blockRepository->add($block);
    }
}
