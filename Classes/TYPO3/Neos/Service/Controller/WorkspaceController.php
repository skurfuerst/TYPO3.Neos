<?php
namespace TYPO3\Neos\Service\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\TypeConverter\NodeConverter;

/**
 * Service Controller for managing Workspaces
 */
class WorkspaceController extends AbstractServiceController {

	/**
	 * @var string
	 */
	protected $defaultViewObjectName = 'TYPO3\Neos\Service\View\NodeView';

	/**
	 * @var \TYPO3\Neos\Service\View\NodeView
	 */
	protected $view;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Service\PublishingService
	 */
	protected $publishingService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Property\PropertyMappingConfigurationBuilder
	 */
	protected $propertyMappingConfigurationBuilder;

	/**
	 * @return void
	 */
	protected function initializeAction() {
		if ($this->arguments->hasArgument('node')) {
			$this
				->arguments
				->getArgument('node')
				->getPropertyMappingConfiguration()
				->setTypeConverterOption('TYPO3\TYPO3CR\TypeConverter\NodeConverter', NodeConverter::REMOVED_CONTENT_SHOWN, TRUE);
		}

		if ($this->arguments->hasArgument('nodes')) {
			$this
				->arguments
				->getArgument('nodes')
				->getPropertyMappingConfiguration()
				->forProperty('*')
				->setTypeConverterOption('TYPO3\TYPO3CR\TypeConverter\NodeConverter', NodeConverter::REMOVED_CONTENT_SHOWN, TRUE);
		}
	}

	/**
	 * Publishes the given node to the specified targetWorkspace
	 *
	 * @param NodeInterface $node
	 * @param string $targetWorkspaceName
	 * @return void
	 */
	public function publishNodeAction(NodeInterface $node, $targetWorkspaceName = NULL) {
		$targetWorkspace = ($targetWorkspaceName !== NULL) ? $this->workspaceRepository->findOneByName($targetWorkspaceName) : NULL;
		$this->publishingService->publishNode($node, $targetWorkspace);

		$this->throwStatus(204, 'Node published', '');
	}

	/**
	 * Publishes the given nodes to the specified targetWorkspace
	 *
	 * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> $nodes
	 * @param string $targetWorkspaceName
	 * @return void
	 */
	public function publishNodesAction(array $nodes, $targetWorkspaceName  = NULL) {
		$targetWorkspace = ($targetWorkspaceName !== NULL) ? $this->workspaceRepository->findOneByName($targetWorkspaceName) : NULL;
		$this->publishingService->publishNodes($nodes, $targetWorkspace);

		$this->throwStatus(204, 'Nodes published', '');
	}

	/**
	 * Discards the given node
	 *
	 * @param NodeInterface $node
	 * @return void
	 */
	public function discardNodeAction(NodeInterface $node) {
		$this->publishingService->discardNode($node);

		$this->throwStatus(204, 'Node changes have been discarded', '');
	}

	/**
	 * Discards the given nodes
	 *
	 * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> $nodes
	 * @return void
	 */
	public function discardNodesAction(array $nodes) {
		$this->publishingService->discardNodes($nodes);

		$this->throwStatus(204, 'Node changes have been discarded', '');
	}

	/**
	 * Publish everything in the workspace with the given workspace name
	 *
	 * @param string $sourceWorkspaceName Name of the source workspace containing the content to publish
	 * @param string $targetWorkspaceName Name of the target workspace the content should be published to
	 * @return void
	 */
	public function publishAllAction($sourceWorkspaceName, $targetWorkspaceName) {
		$sourceWorkspace = $this->workspaceRepository->findOneByName($sourceWorkspaceName);
		$targetWorkspace = $this->workspaceRepository->findOneByName($targetWorkspaceName);
		if ($sourceWorkspace === NULL) {
			$this->throwStatus(400, 'Invalid source workspace');
		}
		if ($targetWorkspace === NULL) {
			$this->throwStatus(400, 'Invalid target workspace');
		}
		$this->publishingService->publishNodes($this->publishingService->getUnpublishedNodes($sourceWorkspace), $targetWorkspace);

		$this->throwStatus(204, sprintf('All changes in workspace %s have been published to %s', $sourceWorkspaceName, $targetWorkspaceName), '');
	}

	/**
	 * Get every unpublished node in the workspace with the given workspace name
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace
	 * @return void
	 */
	public function getWorkspaceWideUnpublishedNodesAction($workspace) {
		$this->view->assignNodes($this->publishingService->getUnpublishedNodes($workspace));
	}

	/**
	 * Discard everything in the workspace with the given workspace name
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace
	 * @return void
	 */
	public function discardAllAction($workspace) {
		$this->publishingService->discardAllNodes($workspace);

		$this->throwStatus(204, 'Workspace changes have been discarded', '');
	}

}
