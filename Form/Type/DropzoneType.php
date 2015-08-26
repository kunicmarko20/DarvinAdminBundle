<?php
/**
 * @author    Igor Nikolaev <igor.sv.n@gmail.com>
 * @copyright Copyright (c) 2015, Darvin Studio
 * @link      https://www.darvin-studio.ru
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Darvin\AdminBundle\Form\Type;

use Darvin\AdminBundle\Form\DataTransformer\DropzoneToUploadablesTransformer;
use Darvin\AdminBundle\Form\FormException;
use Oneup\UploaderBundle\Templating\Helper\UploaderHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Vich\UploaderBundle\Metadata\MetadataReader;

/**
 * Dropzone form type
 */
class DropzoneType extends AbstractType
{
    const DEFAULT_ONEUP_UPLOADER_MAPPING = 'darvin';

    const OPTION_UPLOADABLE_FIELD = 'uploadable_field';

    /**
     * @var \Oneup\UploaderBundle\Templating\Helper\UploaderHelper
     */
    private $oneupUploaderHelper;

    /**
     * @var \Symfony\Component\PropertyAccess\PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var \Vich\UploaderBundle\Metadata\MetadataReader
     */
    private $vichUploaderMetadataReader;

    /**
     * @var array
     */
    private $oneupUploaderConfig;

    /**
     * @param \Oneup\UploaderBundle\Templating\Helper\UploaderHelper      $oneupUploaderHelper        1-up uploader helper
     * @param \Symfony\Component\PropertyAccess\PropertyAccessorInterface $propertyAccessor           Property accessor
     * @param \Vich\UploaderBundle\Metadata\MetadataReader                $vichUploaderMetadataReader Vich uploader metadata reader
     * @param array                                                       $oneupUploaderConfig        1-up uploader configuration
     */
    public function __construct(
        UploaderHelper $oneupUploaderHelper,
        PropertyAccessorInterface $propertyAccessor,
        MetadataReader $vichUploaderMetadataReader,
        array $oneupUploaderConfig
    ) {
        $this->oneupUploaderHelper = $oneupUploaderHelper;
        $this->propertyAccessor = $propertyAccessor;
        $this->vichUploaderMetadataReader = $vichUploaderMetadataReader;
        $this->oneupUploaderConfig = $oneupUploaderConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->validateOptions($options);

        $builder
            ->add('dropzone', 'form', array(
                'label' => false,
                'attr'  => array(
                    'class'          => 'dropzone',
                    'data-filenames' => '.filenames',
                    'data-url'       => $this->oneupUploaderHelper->endpoint($options['oneup_uploader_mapping']),
                ),
            ))
            ->add('filenames', 'collection', array(
                'label'     => false,
                'type'      => 'hidden',
                'allow_add' => true,
                'attr'      => array(
                    'class'         => 'filenames',
                    'data-autoinit' => 0,
                ),
            ))
            ->addModelTransformer(
                new DropzoneToUploadablesTransformer(
                    $this->propertyAccessor,
                    $this->oneupUploaderConfig['mappings'][$options['oneup_uploader_mapping']]['storage']['directory'],
                    $options['uploadable_class'],
                    $this->getUploadableField($options)
                )
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(array(
                'oneup_uploader_mapping' => self::DEFAULT_ONEUP_UPLOADER_MAPPING,
            ))
            ->setDefined(array(
                self::OPTION_UPLOADABLE_FIELD,
            ))
            ->setRequired(array(
                'uploadable_class',
            ))
            ->setAllowedTypes(array(
                'oneup_uploader_mapping' => 'string',
                'uploadable_class'       => 'string',
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'darvin_admin_dropzone';
    }

    /**
     * @param array $options Form options
     *
     * @return string
     * @throws \Darvin\AdminBundle\Form\FormException
     */
    private function getUploadableField(array $options)
    {
        $uploadableClass = $options['uploadable_class'];

        $uploadableFields = array_keys($this->vichUploaderMetadataReader->getUploadableFields($uploadableClass));

        if (empty($uploadableFields)) {
            throw new FormException(sprintf('Class "%s" has no uploadable fields.', $uploadableClass));
        }
        if (isset($options[self::OPTION_UPLOADABLE_FIELD])) {
            if (!in_array($options[self::OPTION_UPLOADABLE_FIELD], $uploadableFields)) {
                $message = sprintf(
                    'Uploadable field "%s" does not exist in class "%s", existing uploadable fields: "%s".',
                    $options[self::OPTION_UPLOADABLE_FIELD],
                    $uploadableClass,
                    implode('", "', $uploadableFields)
                );

                throw new FormException($message);
            }

            $uploadableField = $options[self::OPTION_UPLOADABLE_FIELD];
        } else {
            if (count($uploadableFields) > 1) {
                $message = sprintf(
                    'Class "%s" has more than one uploadable field ("%s") - "%s" form option required.',
                    $uploadableClass,
                    implode('", "', $uploadableFields),
                    self::OPTION_UPLOADABLE_FIELD
                );

                throw new FormException($message);
            }

            $uploadableField = $uploadableFields[0];
        }

        $uploadable = new $uploadableClass();

        if (!$this->propertyAccessor->isWritable($uploadable, $uploadableField)) {
            $message = sprintf(
                'Uploadable field "%s::$%s" is not writable. Make sure it has public access.',
                $uploadableClass,
                $uploadableField
            );

            throw new FormException($message);
        }

        return $uploadableField;
    }

    /**
     * @param array $options Form options
     *
     * @throws \Darvin\AdminBundle\Form\FormException
     */
    private function validateOptions(array $options)
    {
        $oneupUploaderMapping = $options['oneup_uploader_mapping'];

        if (!isset($this->oneupUploaderConfig['mappings'][$oneupUploaderMapping])) {
            $message = sprintf(
                '1-up uploader mapping "%s" does not exist, existing mappings: "%s".',
                $oneupUploaderMapping,
                implode('", "', array_keys($this->oneupUploaderConfig['mappings']))
            );

            throw new FormException($message);
        }

        $uploadableClass = $options['uploadable_class'];

        if (!class_exists($uploadableClass)) {
            throw new FormException(sprintf('Uploadable class "%s" does not exist.', $uploadableClass));
        }
        if (!$this->vichUploaderMetadataReader->isUploadable($uploadableClass)) {
            throw new FormException(sprintf('Class "%s" is not valid uploadable class.', $uploadableClass));
        }
    }
}
