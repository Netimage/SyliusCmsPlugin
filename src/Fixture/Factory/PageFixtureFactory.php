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
use BitBag\SyliusCmsPlugin\Entity\PageImage;
use BitBag\SyliusCmsPlugin\Entity\PageInterface;
use BitBag\SyliusCmsPlugin\Entity\PageTranslationInterface;
use BitBag\SyliusCmsPlugin\Repository\PageRepositoryInterface;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PageFixtureFactory implements FixtureFactoryInterface
{
    /** @var FactoryInterface */
    private $pageFactory;

    /** @var FactoryInterface */
    private $pageTranslationFactory;

    /** @var PageRepositoryInterface */
    private $pageRepository;

    /** @var ImageUploaderInterface */
    private $imageUploader;

    /** @var ProductsAssignerInterface */
    private $productsAssigner;

    /** @var SectionsAssignerInterface */
    private $sectionsAssigner;

    /** @var ChannelsAssignerInterface */
    private $channelAssigner;


    public function __construct(
        FactoryInterface $pageFactory,
        FactoryInterface $pageTranslationFactory,
        PageRepositoryInterface $pageRepository,
        ImageUploaderInterface $imageUploader,
        ProductsAssignerInterface $productsAssigner,
        SectionsAssignerInterface $sectionsAssigner,
        ChannelsAssignerInterface $channelAssigner
    ) {
        $this->pageFactory = $pageFactory;
        $this->pageTranslationFactory = $pageTranslationFactory;
        $this->pageRepository = $pageRepository;
        $this->imageUploader = $imageUploader;
        $this->productsAssigner = $productsAssigner;
        $this->sectionsAssigner = $sectionsAssigner;
        $this->channelAssigner = $channelAssigner;
    }

    public function load(array $data): void
    {
        foreach ($data as $code => $fields) {
            if (
                true === $fields['remove_existing'] &&
                null !== $page = $this->pageRepository->findOneBy(['code' => $code])
            ) {
                $this->pageRepository->remove($page);
            }

            if (null !== $fields['number']) {
                for ($i = 0; $i < $fields['number']; ++$i) {
                    $this->createPage(md5(uniqid()), $fields, true);
                }
            } else {
                $this->createPage($code, $fields);
            }
        }
    }

    private function createPage(string $code, array $pageData, bool $generateSlug = false): void
    {
        /** @var PageInterface $page */
        $page = $this->pageFactory->createNew();

        $this->sectionsAssigner->assign($page, $pageData['sections']);
        $this->productsAssigner->assign($page, $pageData['products']);
        $this->channelAssigner->assign($page, $pageData['channels']);

        $page->setCode($code);
        $page->setEnabled($pageData['enabled']);

        foreach ($pageData['translations'] as $localeCode => $translation) {
            /** @var PageTranslationInterface $pageTranslation */
            $pageTranslation = $this->pageTranslationFactory->createNew();
            $slug = true === $generateSlug ? md5(uniqid()) : $translation['slug'];

            $pageTranslation->setLocale($localeCode);
            $pageTranslation->setSlug($slug);
            $pageTranslation->setName($translation['name']);
            $pageTranslation->setMetaKeywords($translation['meta_keywords']);
            $pageTranslation->setMetaDescription($translation['meta_description']);
            $pageTranslation->setContent($translation['content']);

            if ($translation['image_path']) {
                $image = new PageImage();
                $path = $translation['image_path'];
                $uploadedImage = new UploadedFile($path, md5($path) . '.jpg');

                $image->setFile($uploadedImage);
                $pageTranslation->setImage($image);

                $this->imageUploader->upload($image);
            }

            $page->addTranslation($pageTranslation);
        }

        $this->pageRepository->add($page);
    }
}
