<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Bundle\Block\Catalog\Product\View\Type;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * Class consist of basic logic for bundle options view
 */
abstract class AbstractBundleOptionsViewTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var LayoutInterface */
    private $layout;

    /** @var SerializerInterface */
    private $serializer;

    /** @var Registry */
    private $registry;

    /** @var PageFactory */
    private $pageFactory;

    /** @var ProductResource */
    private $productResource;

    /** @var string */
    private $selectLabelXpath;

    /** @var string */
    private $backToProductDetailButtonXpath;

    /** @var string */
    private $titleXpath;

    /** @var string */
    private $singleOptionXpath;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->productRepository->cleanCache();
        $this->layout = $this->objectManager->get(LayoutInterface::class);
        $this->serializer = $this->objectManager->get(SerializerInterface::class);
        $this->registry = $this->objectManager->get(Registry::class);
        $this->pageFactory = $this->objectManager->get(PageFactory::class);
        $this->productResource = $this->objectManager->get(ProductResource::class);
        $this->selectLabelXpath = "//fieldset[contains(@class, 'fieldset-bundle-options')] //label/span[text() = '%s']";
        $this->backToProductDetailButtonXpath = "//button[contains(@class, 'back customization')]";
        $this->titleXpath = "//fieldset[contains(@class, 'bundle-options')"
            . "and //span[contains(text(), 'Customize %s')]]";
        $this->singleOptionXpath = "//input[contains(@class, 'bundle-option') and contains(@type, 'hidden')]";
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        $this->registry->unregister('product');
        $this->registry->unregister('current_product');

        parent::tearDown();
    }

    /**
     * Process bundle options view with few selections
     *
     * @param string $sku
     * @param string $optionsSelectLabel
     * @param array $expectedSelectionsNames
     * @param bool $requiredOption
     * @return void
     */
    protected function processMultiSelectionsView(
        string $sku,
        string $optionsSelectLabel,
        array $expectedSelectionsNames,
        bool $requiredOption = false
    ): void {
        $product = $this->productRepository->get($sku);
        $result = $this->renderProductOptionsBlock($product);
        $this->assertEquals(1, Xpath::getElementsCountForXpath($this->backToProductDetailButtonXpath, $result));
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(sprintf($this->selectLabelXpath, $optionsSelectLabel), $result)
        );
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(sprintf($this->titleXpath, $product->getName()), $result)
        );
        $selectPath = $requiredOption ? $this->getRequiredSelectXpath() : $this->getNotRequiredSelectXpath();
        foreach ($expectedSelectionsNames as $selection) {
            $this->assertEquals(1, Xpath::getElementsCountForXpath(sprintf($selectPath, $selection), $result));
        }
    }

    /**
     * Process bundle options view with single selection
     *
     * @param string $sku
     * @param string $optionsSelectLabel
     * @return void
     */
    protected function processSingleSelectionView(string $sku, string $optionsSelectLabel): void
    {
        $product = $this->productRepository->get($sku);
        $result = $this->renderProductOptionsBlock($product);
        $this->assertEquals(1, Xpath::getElementsCountForXpath($this->backToProductDetailButtonXpath, $result));
        $this->assertEquals(
            1,
            Xpath::getElementsCountForXpath(sprintf($this->selectLabelXpath, $optionsSelectLabel), $result)
        );
        $this->assertEquals(1, Xpath::getElementsCountForXpath($this->singleOptionXpath, $result));
    }

    /**
     * Register product
     *
     * @param ProductInterface $product
     * @return void
     */
    private function registerProduct(ProductInterface $product): void
    {
        $this->registry->unregister('product');
        $this->registry->unregister('current_product');
        $this->registry->register('product', $product);
        $this->registry->register('current_product', $product);
    }

    /**
     * Render bundle product options block
     *
     * @param ProductInterface $product
     * @return string
     */
    private function renderProductOptionsBlock(ProductInterface $product): string
    {
        $this->registerProduct($product);
        $page = $this->pageFactory->create();
        $page->addHandle(['default', 'catalog_product_view', 'catalog_product_view_type_bundle']);
        $page->getLayout()->generateXml();
        $block = $page->getLayout()->getBlock('product.info.bundle.options');

        return $block->toHtml();
    }

    /**
     * Get not required select Xpath
     *
     * @return string
     */
    abstract protected function getRequiredSelectXpath(): string;

    /**
     * Get not required select Xpath
     *
     * @return string
     */
    abstract protected function getNotRequiredSelectXpath(): string;
}
