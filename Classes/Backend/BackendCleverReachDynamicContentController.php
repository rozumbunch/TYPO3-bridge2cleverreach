<?php

namespace Rozumbunch\Bridge2Cleverreach\Backend;

use Rozumbunch\Bridge2Cleverreach\Domain\Repository\ContentElementsRepository;
use Rozumbunch\Bridge2Cleverreach\Domain\Repository\NewsRepository;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rozumbunch\Bridge2Cleverreach\Api\ApiManager;
use Rozumbunch\Bridge2Cleverreach\Services\DynamicContentService;
use Rozumbunch\Bridge2Cleverreach\Utility\SitePageTree;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Enum\ModuleLayout;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class BackendCleverReachDynamicContentController extends ActionController
{
	const REGISTRY_REGISTRATION_IDENTIFICATOR_NAMESPACE = 'backendCleverReachDynamicContentRegistration';
	const REGISTRY_REGISTRATION_IDENTIFICATOR_KEY = 'contentRegistered';
	const DEFAULT_DYNAMIC_CONTENT_NAME = 'TYPO3 Dynamic Content';

	/**
	 * Selected page from the page tree. Zero means no page is selected.
	 *
	 * @var int
	 */
	protected $id;

	protected bool $hasSelectedPage = false;

	/**
	 * @var int
	 */
	protected $rootPageId;

	/**
	 * @var Site
	 */
	protected $site;

	/** @var RequestFactory */
	protected $requestFactory;

	/**
	 * @param ModuleTemplateFactory $moduleTemplateFactory
	 * @param ResponseFactoryInterface $responseFactory
	 * @param ResourceFactory $resourceFactory
	 * @param PageRenderer $pageRenderer
	 * @param DataHandler $dataHandler
	 * @param IconFactory $iconFactory
	 * @param ApiManager $apiManager
	 */
	public function __construct(
		protected readonly ModuleTemplateFactory $moduleTemplateFactory,
		ResponseFactoryInterface                 $responseFactory,
		protected readonly ResourceFactory       $resourceFactory,
		private readonly PageRenderer             $pageRenderer,
		protected readonly DataHandler           $dataHandler,
		protected IconFactory $iconFactory,
		protected ApiManager $apiManager,
		protected NewsRepository $newsRepository,
		protected ContentElementsRepository $contentElementsRepository,
		protected readonly ModuleProvider $moduleProvider,
	)
	{
		$this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
		// @extensionScannerIgnoreLine
		$this->id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
		$this->resolveSite();
	}

	/**
	 * The site is taken from the page selected in the page tree.
	 */
	protected function resolveSite(): void
	{
		if ($this->id <= 0) {
			return;
		}

		try {
			$site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId($this->id);
		} catch (\TYPO3\CMS\Core\Exception\SiteNotFoundException) {
			return;
		}

		$this->site = $site;
		$this->rootPageId = $site->getRootPageId();
		$this->hasSelectedPage = true;
	}

	/**
	 * Card overview of the submodules. Unlike the core overview this does not
	 * attach the current page to the breadcrumb.
	 */
	public function overviewAction(ServerRequestInterface $request): ResponseInterface
	{
		$languageService = $this->getLanguageService();
		$currentModule = $request->getAttribute('module');
		$view = $this->moduleTemplateFactory->create($request);
		$view->setLayout(ModuleLayout::NORMAL);
		$view->makeDocHeaderModuleMenu($this->moduleMenuParameters());

		if (!$currentModule instanceof ModuleInterface) {
			return $view->renderResponse('Backend/DynamicContent/Overview');
		}

		$moduleTitle = $languageService->sL($currentModule->getTitle());
		$view->setTitle($moduleTitle);
		$view->getDocHeaderComponent()->setShortcutContext($currentModule->getIdentifier(), $moduleTitle);

		$submodules = [];
		if ($this->hasSelectedPage) {
			foreach ($currentModule->getSubModules() as $submodule) {
				if (!$this->moduleProvider->accessGranted($submodule->getIdentifier(), $this->getBackendUser())) {
					continue;
				}
				$submodules[] = [
					'module' => $submodule,
					'disabled' => $this->isSubmoduleDisabledBySetting($submodule->getIdentifier()),
				];
			}
		}

		$view->assignMultiple([
			'currentModule' => $currentModule,
			'submodules' => $submodules,
			'siteSelectionRequired' => !$this->hasSelectedPage,
			'additionalParameters' => $this->moduleMenuParameters(),
		]);

		return $view->renderResponse('Backend/DynamicContent/Overview');
	}

	public function handleRequest(ServerRequestInterface $request): ResponseInterface
	{
		if (!$this->hasSelectedPage) {
			return $this->noSiteFoundAction($request);
		}

		$currentModule = $request->getAttribute('module');
		$moduleIdentifier = $currentModule->getIdentifier();

		switch ($moduleIdentifier) {
			case 'web_dynamicContentRegister':
				return $this->registerAction($request);
			case 'web_dynamicContentNews':
				if (!ExtensionManagementUtility::isLoaded('news')) {
					return $this->newsExtensionMissingAction($request);
				}
				if (!$this->isDynamicContentNewsEnabled()) {
					return $this->overviewAction($request);
				}
				return $this->newsAction($request);
			case 'web_dynamicContentElements':
				return $this->contentElementsAction($request);
			case 'web_dynamicContentCustom':
				if (!$this->isDynamicContentCustomEnabled()) {
					return $this->overviewAction($request);
				}
				return $this->customAction($request);
		}

		return $this->indexAction($request);
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function indexAction(ServerRequestInterface $request): ResponseInterface
	{
		$currentModule = $request->getAttribute('module');
		$pageRecord = BackendUtility::readPageAccess($this->id, '1=1') ?: [];
		$languageService = $this->getLanguageService();

		$view = $this->moduleTemplateFactory->create($request);
		$view->setTitle($languageService->sL($currentModule->getTitle()), $pageRecord['title']);
		$view->getDocHeaderComponent()->setMetaInformation($pageRecord);
//		$this->addPreviewButtonToDocHeader($view, $pageRecord);
//		$this->addShortcutButtonToDocHeader($view, $currentModuleIdentifier, $pageRecord, $pageUid);
		$view->makeDocHeaderModuleMenu($this->moduleMenuParameters());
		$view->assignMultiple([
			'pageUid' => $this->id,
//			'previousPage' => $this->getClosestAncestorPageWithTemplateRecord($pageUid),
//			'templateRecord' => $currentTemplateRecord,
//			'allTemplatesOnPage' => $allTemplatesOnPage,
			'numberOfConstantsLines' => trim((string)($currentTemplateRecord['constants'] ?? '')) ? count(explode(LF, (string)$currentTemplateRecord['constants'])) : 0,
			'numberOfSetupLines' => trim((string)($currentTemplateRecord['config'] ?? '')) ? count(explode(LF, (string)$currentTemplateRecord['config'])) : 0,
		]);
//		return $view->renderResponse('InfoModifyMain');
//		DebugUtility::debug($view->getDocHeaderComponent());
		return $view->renderResponse('Backend/DynamicContent/Index');
	}

	public function registerAction(ServerRequestInterface $request): ResponseInterface
	{
		$parsedBody = $request->getParsedBody();
		$registry = self::getRegistry();
		$isContentRegistered = (bool)$registry->get(
			self::REGISTRY_REGISTRATION_IDENTIFICATOR_NAMESPACE,
			$this->registrationRegistryKey()
		);
		$error = null;

		$access = $this->authorizeApiManager();
		if (!empty($access['error']) || empty($access['access_token'])) {
			$error = (string)($access['message'] ?? $this->translate('module.dynamicContentRegister.error.authorization'));
		} else {
			$token = (string)$access['access_token'];
			if (isset($parsedBody['registerContent'])) {
				$result = $this->apiManager->setClevereachMyContent(
					$this->dynamicContentName(),
					$this->buildDynamicContentEndpointUrl(),
					$token
				);
				if (!empty($result['error'])) {
					$error = (string)($result['message'] ?? $this->translate('module.dynamicContentRegister.error.authorization'));
				}
			} elseif (isset($parsedBody['unregisterContent'])) {
				$error = $this->unregisterDynamicContent($token) ?? $error;
			}

			try {
				$isContentRegistered = $this->findRegisteredDynamicContent($token) !== null;
				$this->storeRegistrationState($isContentRegistered);
			} catch (\Throwable $exception) {
				$error ??= $exception->getMessage();
			}
		}

		$currentModule = $request->getAttribute('module');
		$view = $this->moduleTemplateFactory->create($request);
		$view->makeDocHeaderModuleMenu($this->moduleMenuParameters());
		if ($currentModule instanceof ModuleInterface) {
			$view->setTitle($this->getLanguageService()->sL($currentModule->getTitle()));
		}
		$view->assignMultiple([
			'currentModule' => $currentModule,
			'isContentRegistered' => $isContentRegistered,
			'error' => $error,
		]);
		return $view->renderResponse('Backend/DynamicContent/Register');
	}

	protected function buildDynamicContentEndpointUrl(): string
	{
		$uri = $this->site->getRouter()->generateUri(
			$this->site->getRootPageId(),
			[
				'type' => 1705405176,
			]
		);

		return (string)$uri;
	}

	/**
	 * Live status from CleverReach GET /v3/mycontent.json.
	 * The local registry is only a cache of this result.
	 */
	protected function findRegisteredDynamicContent(string $token): ?object
	{
		$myContent = $this->apiManager->getClevereachMyContent($token);
		if (!is_array($myContent)) {
			return null;
		}

		foreach ($myContent as $content) {
			if (is_object($content) && $this->contentBelongsToCurrentSite($content)) {
				return $content;
			}
		}

		return null;
	}

	protected function unregisterDynamicContent(string $token): ?string
	{
		$registeredContent = $this->findRegisteredDynamicContent($token);
		if ($registeredContent === null) {
			$this->storeRegistrationState(false);
			return null;
		}

		$this->apiManager->removeClevereachMyContent($registeredContent->id, $token);

		return null;
	}

	protected function storeRegistrationState(bool $isRegistered): void
	{
		$registry = self::getRegistry();
		if ($isRegistered) {
			$registry->set(
				self::REGISTRY_REGISTRATION_IDENTIFICATOR_NAMESPACE,
				$this->registrationRegistryKey(),
				1
			);
			return;
		}

		$registry->remove(
			self::REGISTRY_REGISTRATION_IDENTIFICATOR_NAMESPACE,
			$this->registrationRegistryKey()
		);
	}

	/**
	 * One registry entry per site root page. The old global key ignored every other site.
	 */
	protected function registrationRegistryKey(): string
	{
		return self::REGISTRY_REGISTRATION_IDENTIFICATOR_KEY . '-' . (int)$this->rootPageId;
	}

	/**
	 * CleverReach stores one "My content" entry per name. The site identifier keeps
	 * two sites on the same account from sharing a single registration.
	 */
	protected function dynamicContentName(): string
	{
		$identifier = $this->site instanceof Site ? $this->site->getIdentifier() : (string)$this->rootPageId;

		return self::DEFAULT_DYNAMIC_CONTENT_NAME . ' (' . $identifier . ')';
	}

	protected function contentBelongsToCurrentSite(object $content): bool
	{
		$name = (string)($content->name ?? '');
		if ($name === $this->dynamicContentName()) {
			return true;
		}
		if ($name !== self::DEFAULT_DYNAMIC_CONTENT_NAME || !$this->site instanceof Site) {
			return false;
		}

		$host = parse_url((string)$this->site->getBase(), PHP_URL_HOST);

		return is_string($host) && $host !== '' && str_contains((string)($content->url ?? ''), $host);
	}

	/**
	 * Keeps the selected page when switching between the submodules.
	 *
	 * @return array<string, int>
	 */
	protected function moduleMenuParameters(): array
	{
		if (!$this->hasSelectedPage) {
			return [];
		}

		return ['id' => $this->id];
	}

	/**
	 * @return array<string, string>
	 */
	protected function contentTypeLabels(): array
	{
		$labels = [];
		$items = $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] ?? [];
		foreach ($items as $item) {
			if (!is_array($item)) {
				continue;
			}
			$value = (string)($item['value'] ?? $item[1] ?? '');
			if ($value === '' || $value === '--div--') {
				continue;
			}
			$label = (string)($item['label'] ?? $item[0] ?? $value);
			$translated = $this->getLanguageService()->sL($label);
			$labels[$value] = $translated !== '' ? $translated : $label;
		}

		return $labels;
	}

	/**
	 * @param array<string, mixed> $element
	 * @param array<string, string> $contentTypeLabels
	 */
	protected function formatContentElementLabel(array $element, array $contentTypeLabels): string
	{
		$cType = (string)($element['CType'] ?? '');
		$typeLabel = $contentTypeLabels[$cType] ?? ($cType !== '' ? $cType : 'Content');
		$label = $typeLabel . ' [ce-' . (int)$element['uid'] . '->page-' . (int)($element['pid'] ?? 0) . ']';
		$title = trim((string)($element['header'] ?? ''));
		if ($title !== '') {
			$label .= '-' . $title;
		}

		return $label;
	}

	protected function isSubmoduleDisabledBySetting(string $identifier): bool
	{
		return match ($identifier) {
			'web_dynamicContentNews' => !$this->isDynamicContentNewsEnabled(),
			'web_dynamicContentCustom' => !$this->isDynamicContentCustomEnabled(),
			default => false,
		};
	}

	/**
	 * Site setting "Dynamische Inhalte als News einbinden".
	 */
	protected function isDynamicContentNewsEnabled(): bool
	{
		return $this->isSiteSettingEnabled('bridge2cleverreach.dynamicContentsAsNews');
	}

	/**
	 * Site setting "Selbst definierte Dynamic-Content einbinden".
	 */
	protected function isDynamicContentCustomEnabled(): bool
	{
		return $this->isSiteSettingEnabled('bridge2cleverreach.dynamicContentsCustom');
	}

	protected function isSiteSettingEnabled(string $identifier): bool
	{
		if (!$this->hasSelectedPage || !$this->site instanceof Site) {
			return false;
		}

		$value = $this->site->getSettings()->get($identifier, false);

		return $value === true || $value === 1 || $value === '1';
	}

	protected function translate(string $key): string
	{
		return $this->getLanguageService()->sL(
			'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:' . $key
		);
	}

	/**
	 * Authorizes against CleverReach with cleverreachClientId and
	 * cleverreachClientSecret from the site configuration (config.yaml).
	 *
	 * @return array<string, mixed>
	 */
	protected function authorizeApiManager(): array
	{
		$siteConfiguration = $this->site->getConfiguration();

		return $this->apiManager->authorize(
			(string)($siteConfiguration['cleverreachClientId'] ?? ''),
			(string)($siteConfiguration['cleverreachClientSecret'] ?? '')
		);
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 * @throws \Doctrine\DBAL\Exception
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException
	 */
	public function newsAction(ServerRequestInterface $request): ResponseInterface
	{
		$parsedBody = $request->getParsedBody();
		if(isset($parsedBody['selectedNews']) && isset($parsedBody['_savedok'])){
			$selectedNews = $parsedBody['selectedNews'];
			$this->newsRepository->setSelectedNewsUidsForApiCleverReach($selectedNews, $this->rootPageId);
		}

		$this->pageRenderer->loadJavaScriptModule('@Rozumbunch/Bridge2Cleverreach/multipleSideBySide.js');
		$currentModule = $request->getAttribute('module');
		$view = $this->moduleTemplateFactory->create($request);
		$view = $this->addDocHeaderButtons($view);
		$view->makeDocHeaderModuleMenu($this->moduleMenuParameters());
		if ($currentModule instanceof ModuleInterface) {
			$view->setTitle($this->getLanguageService()->sL($currentModule->getTitle()));
		}

		$selectedNews = $this->newsRepository->getSelectedNewsUidsForApiCleverReach((int)$this->rootPageId);

		$selectedNewsArray = [];
		$selectedNewsString = [];
		if(!empty($selectedNews)){
			foreach ($selectedNews as $news){
				$selectedNewsArray[$news['uid']] = $news;
				$selectedNewsString[] = $news['uid'];
			}
		}
		$selectedNewsString = implode(',', $selectedNewsString);

		$view->assignMultiple([
			'currentModule' => $currentModule,
			'news' => $this->newsRepository->getAllNews((int)$this->rootPageId),
			'selectedNews' => $selectedNewsArray,
			'selectedNewsString' => $selectedNewsString,
		]);
		return $view->renderResponse('Backend/DynamicContent/News');
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function contentElementsAction(ServerRequestInterface $request): ResponseInterface
	{
		$allowedCTypes = GeneralUtility::trimExplode(
			',',
			(string)(DynamicContentService::getDynamicContentConfig(ContentElementsRepository::TYPE)['allowedCTypes'] ?? ''),
			true
		);
		$parsedBody = $request->getParsedBody();
		if(isset($parsedBody['_savedok'])){
			if(isset($parsedBody['contentFromPids'])){
				$sourcePageIds = $this->contentElementsRepository->filterPageIdsToSite(
					(int)$this->rootPageId,
					(string)$parsedBody['contentFromPids']
				);
				$this->contentElementsRepository->setSourcePageUids(implode(',', $sourcePageIds), $this->rootPageId);
			}
			if(isset($parsedBody['selectedContentElements'])){
				$sourcePageIds = $this->contentElementsRepository->filterPageIdsToSite(
					(int)$this->rootPageId,
					$this->contentElementsRepository->getSourcePageUids((int)$this->rootPageId)
				);
				$selectedElements = $this->contentElementsRepository->filterSelectableContentElements(
					GeneralUtility::intExplode(',', (string)$parsedBody['selectedContentElements'], true),
					$sourcePageIds,
					$allowedCTypes
				);
				$this->contentElementsRepository->setSelectedContentElementUidsForApiCleverReach(
					implode(',', array_column($selectedElements, 'uid')),
					$this->rootPageId
				);
			}
		}

		$this->pageRenderer->loadJavaScriptModule('@Rozumbunch/Bridge2Cleverreach/multipleSideBySide.js');
		$currentModule = $request->getAttribute('module');
		$view = $this->moduleTemplateFactory->create($request);
		$view = $this->addDocHeaderButtons($view);
		$view->makeDocHeaderModuleMenu($this->moduleMenuParameters());
		if ($currentModule instanceof ModuleInterface) {
			$view->setTitle($this->getLanguageService()->sL($currentModule->getTitle()));
		}

		$sourcePages = $this->contentElementsRepository->getSourcePages($this->rootPageId);
		$sourcePageUids = array_column($sourcePages, 'uid');

		$allowedElements = !empty($sourcePageUids)
			? $this->contentElementsRepository->getContentElementsByPids($sourcePageUids, $allowedCTypes)
			: [];

		$selectedContentElements = $this->contentElementsRepository->getSelectedContentElementsForApiCleverReach((int)$this->rootPageId, $allowedCTypes);

		$contentTypeLabels = $this->contentTypeLabels();
		$elementLabels = [];
		foreach ($allowedElements as $contentElement) {
			$elementLabels[(int)$contentElement['uid']] = $this->formatContentElementLabel($contentElement, $contentTypeLabels);
		}
		foreach ($selectedContentElements as &$contentElement) {
			$contentElement['label'] = $this->formatContentElementLabel($contentElement, $contentTypeLabels);
		}
		unset($contentElement);

		$selectedContentElementsString = implode(',', array_column($selectedContentElements, 'uid'));

		$view->assignMultiple([
			'currentModule' => $currentModule,
			'rootPageId' => (int)$this->rootPageId,
			'sitePageIds' => implode(',', SitePageTree::collectPageIds((int)$this->rootPageId)),
			'sourcePages' => $sourcePages,
			'sourcePagesString' => implode(',', $sourcePageUids),
			'elementBrowserUrl' => (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('wizard_element_browser'),
			'allowedCTypes' => implode(', ', array_map(
				static fn(string $cType): string => $contentTypeLabels[$cType] ?? $cType,
				$allowedCTypes
			)),
			'allowedElementIds' => implode(',', array_column($allowedElements, 'uid')),
			'elementLabelsJson' => (string)json_encode($elementLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
			'selectedContentElements' => $selectedContentElements,
			'selectedContentElementsString' => $selectedContentElementsString,
		]);
		return $view->renderResponse('Backend/DynamicContent/ContentElements');
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function customAction(ServerRequestInterface $request): ResponseInterface
	{
		$parsedBody = $request->getParsedBody();
		if(isset($parsedBody['selectedContents']) && isset($parsedBody['_savedok'])){
			$selectedContents = $parsedBody['selectedContents'];
			DynamicContentService::setSelectedCustomDynamicContentsForApiCleverReach($selectedContents, $this->rootPageId);
		}

		$this->pageRenderer->loadJavaScriptModule('@Rozumbunch/Bridge2Cleverreach/multipleSideBySide.js');
		$view = $this->moduleTemplateFactory->create($request);
		$view = $this->addDocHeaderButtons($view);
		$view->makeDocHeaderModuleMenu($this->moduleMenuParameters());

		$selectedContents = [];

		$selectedContentsString = DynamicContentService::getSelectedCustomDynamicContents($this->rootPageId);
		$selectedContentsString = isset($selectedContentsString[0]['data']) ? $selectedContentsString[0]['data'] : '';
		if(!empty($selectedContentsString)){
			foreach (explode(',', $selectedContentsString) as $content){
				$selectedContents[$content] = $content;
			}
		}

		$view->assignMultiple([
			'contents' => DynamicContentService::getCustomDynamicContents(),
			'selectedContents' => $selectedContents,
			'selectedContentsString' => $selectedContentsString,
		]);
		return $view->renderResponse('Backend/DynamicContent/Custom');
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService(): LanguageService
	{
		return $GLOBALS['LANG'];
	}

	protected function getBackendUser(): BackendUserAuthentication
	{
		return $GLOBALS['BE_USER'];
	}

	/**
	 * @return mixed|object|\Psr\Log\LoggerAwareInterface|Registry|(Registry&\Psr\Log\LoggerAwareInterface)|(Registry&\TYPO3\CMS\Core\SingletonInterface)|\TYPO3\CMS\Core\SingletonInterface|null
	 */
	public static function getRegistry()
	{
		return GeneralUtility::makeInstance(Registry::class);
	}

	/**
	 * @param $view
	 * @return mixed
	 */
	public function addDocHeaderButtons($view)
	{
		$buttonBar = $view->getDocHeaderComponent()->getButtonBar();

		$saveButton = $buttonBar->makeInputButton()
			->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.saveDoc'))
			->setName('_savedok')
			->setValue('1')
			->setShowLabelText(true)
			->setForm('BackendCleverReachDynamicContentController')
			->setIcon($this->iconFactory->getIcon('actions-document-save', $this->getSmallIconSize()));
		$buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 2);

		return $view;
	}

	/**
	 * IconFactory::getIcon() takes a plain string size on TYPO3 12.4
	 * (the IconSize enum was only introduced in v13) but strictly requires
	 * the IconSize enum from v13 on - the plain string would be a TypeError
	 * there. IconSize::SMALL's backing value is the same string 'small'
	 * v12.4 expects, so this keeps both call shapes working.
	 *
	 * @return \TYPO3\CMS\Core\Imaging\IconSize|string
	 */
	protected function getSmallIconSize()
	{
		if (class_exists(\TYPO3\CMS\Core\Imaging\IconSize::class)) {
			return \TYPO3\CMS\Core\Imaging\IconSize::SMALL;
		}
		return 'small';
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	private function noSiteFoundAction(ServerRequestInterface $request)
	{
		$view = $this->moduleTemplateFactory->create($request);
		$view->makeDocHeaderModuleMenu($this->moduleMenuParameters());
		return $view->renderResponse('Backend/DynamicContent/NoSiteFound');
	}

	/**
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	private function newsExtensionMissingAction(ServerRequestInterface $request): ResponseInterface
	{
		$view = $this->moduleTemplateFactory->create($request);
		$view->makeDocHeaderModuleMenu($this->moduleMenuParameters());
		return $view->renderResponse('Backend/DynamicContent/NewsUnavailable');
	}
}
