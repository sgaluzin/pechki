<?php
/*
 * images.php
 * 
 * Copyright 2013 Bass <support@joomshopping.pro>
 * 
 */
 
// no direct access
defined('_JEXEC') or die;

require_once( dirname(__FILE__).DS."..".DS."type.php");
jimport('joomla.image.image');


class JImageJsf extends JImage
{
	public function __construct ($resource = null)
	{
		parent::__construct($resource);
	}
	
	public function __destruct ()
	{
		parent::__destruct();
	}

	
	public function getHandle ()
	{
		return $this->handle;
	}

	
	public function copyTo ($dst, $x, $y)
	{
		$w = $this->getWidth();
		$h = $this->getHeight();
		
		if ( $this->isTransparent() )
		{
			// Get the transparent color values for the current image.
			$rgba = imageColorsForIndex($this->handle, imagecolortransparent($this->handle));
			$color = imageColorAllocateAlpha($this->handle, $rgba['red'], $rgba['green'], $rgba['blue'], $rgba['alpha']);

			// Set the transparent color values for the new image.
			imagecolortransparent($dst->getHandle(), $color);
			imagefill($handle, 0, 0, $color);

			// imagecopyresized($dst->getHandle(), $this->handle, $x, $y, 0, 0, $dimensions->width, $dimensions->height, $this->getWidth(), $this->getHeight());
			imagecopyresampled($dst->getHandle(), $this->handle, $x, $y, 0, 0, $w, $h, $w, $h);
		}
		else
		{
			imagecopyresampled($dst->getHandle(), $this->handle, $x, $y, 0, 0, $w, $h, $w, $h);
		}
	}
}


class JsfilterTypeImages extends JsfilterType
{
	// Директория для сохранения файлов спрайтов
	private $outPath = '';
	

	// ------------------------------------------------------------------------
	// Конструктор
	// ------------------------------------------------------------------------
	
	function __construct()
	{
		$this->name	= basename(__FILE__, ".php");
		$this->title= 'MJSF_TYPE_IMAGES';

		$this->outPath = JPATH_ROOT.'/modules/mod_jsfilter/assets/sprites';
	}


	// ------------------------------------------------------------------------
	// Возвращает доп.параметры для диалога настройки (конструктор)
	// ------------------------------------------------------------------------
	// \return	Массив со значением текста подсказки к полю и
	//			html код дополнительных элементов для вывода на странице настроек
	// ------------------------------------------------------------------------

	public function getExtSettings ()
	{
		// Поле для ввода количества формируемых столбцов
		$html = '<input type="text" id="struct_ex_type" name="cfg[struct][ex_type][]" value="" />';
		return array(JText::_('MJSF_EXT_SETTINGS_IMAGES_TIP'), $html);
	}


	// ------------------------------------------------------------------------
	// Отрисовка элементов блока
	// ------------------------------------------------------------------------
	// name		Имя элемента формы
	// val		Выбранное значений параметра (из URL)
	// \return	Html код элемента
	// ------------------------------------------------------------------------

	public function render ($name, &$val)
	{
		$jsCfg = JSFactory::getConfig();
		$params = trim($this->params->ex_type);

		list($columns, $imgSizes) = explode('|', $params);
		$imgSizes = trim($imgSizes);
		list($img_width, $img_height) = explode('*', $imgSizes);

		if ( !is_numeric($columns) ) {
			$columns = 1;
		} else {
			$columns = (int) $columns;
		}

		if ( !$imgSizes ) {
			$img_width = 50;
		}

		// Генерация спрайта
		$cache	= JFactory::getCache('jsfilter', 'callback');
		$cState = $cache->getCaching();
		$cache->setCaching(1);
		// Время кэширования - месяц (очистка при изменении конфигурации)
		$cache->setLifeTime(60 * 24 * 30);
		$hashName = $cache->get( array($this, 'buildSprite'), array(&$this->values, $img_width, $img_height) );
		$cache->setCaching($cState);

		// Подключение css файла
		$sitePath = str_replace(JPATH_ROOT.'/', '', $this->outPath);
		JHtml::_('stylesheet', $sitePath.'/'.$hashName.'.css');
		
		// Определение кол-ва элементов в каждом столбце
		$colCount = ($columns) ? ceil( count($this->values) / $columns ) : 0;
		
		$html = '';
		$count = $colCount;
		foreach ( $this->values as $k => &$opt )
		{
			if ($columns && $count == $colCount) {
				$html .= '<div style="display:inline-block;vertical-align:top;width:'.(int)(100 / $columns).'%">';
			}
			$html .= '<label class="images c_'.$hashName.' c_'.$hashName.'_'.$k.'" title="'.$opt->text.'">'
						.'<input type="checkbox" autocomplete="off" style="display:none"'
							.' value="'.$opt->value.'"'
							.' name="'.$name.'[]"'
							.( ( in_array($opt->value, (array)$val) ) ? ' checked="checked"' : '' )
						.'>'
					.'</label>';

			--$count;
			if ($columns > 1 && $count == 0) {
				$html .= '</div>';
				$count = $colCount;
			}
		}

		if ($columns > 1 && $count && $count != $colCount) {
			$html .= '</div>';
		}
		
		return $html;
	}

	
	// ------------------------------------------------------------------------
	// Формирование условия для фильтрации данных в БД
	// ------------------------------------------------------------------------
	// fid		Поле со значением ID фильтруемого параметра
	// fname	Поле с названием фильтруемого параметра
	// val		Значение из формы фильтра
	// \return	Набор условий фильтраци (для вставки в запрос к БД)
	//			для каждого выбранного значения в блоке
	// ------------------------------------------------------------------------

	public function getCondition ($fid, $fname, &$val)
	{
		$db		= JFactory::getDbo();
		$val 	= array_unique($val);
		$cond	= null;

		foreach ($val as $k => &$v) {
			if ( !$v ) {
				unset($val[$k]);
				continue;
			}

			$cond[] = $fid." = ".$db->quote($v);
		}

		return $cond;
	}

	
	// ------------------------------------------------------------------------
	// Генерация спрайтов из изображений
	// ------------------------------------------------------------------------
	// list		Список значений с файлами изображений
	// width	Ширина изображения
	// height	Высота изображения
	// \return	Сгенерированный хэш (имя файла)
	// ------------------------------------------------------------------------

	public function buildSprite (&$list, $width, $height)
	{
		$jsCfg = JSFactory::getConfig();
		
		// Определение габаритов спрайта
		$spriteWidth = $width;
		$spriteHeight = 0;
		$imgList = array();
			
		foreach ($list as $k => &$item) {
			if (!$item->img) continue;

			if ( !JFile::exists($jsCfg->image_attributes_path.'/'.$item->img) ) {
				unset( $list[$k] );
				continue;
			}

			$imgSize = getimagesize($jsCfg->image_attributes_path.'/'.$item->img);

			$imgInfo = array(
				'file' => $jsCfg->image_attributes_path.'/'.$item->img,
				'type' => $imgSize[2]
			);

			if ($width && $height) {
				$spriteHeight += $height;
				$imgInfo['width'] = $width;
				$imgInfo['height'] = $height;
				
			} else {
				// Вычисление ширины изображения
				if ($width) {
					$imgInfo['width'] = $width;
				} else {
					$w = $imgSize[0] * $height / $imgSize[1];
					$imgInfo['width'] = $w;
					
					if ($spriteWidth < $w) {
						$spriteWidth = $w;
					}
				}

				// Вычисление высоты изображения
				if ($height) {
					$imgInfo['height'] = $height;
					$spriteHeight += $height;
				} else {
					$h = $imgSize[1] * $width / $imgSize[0];
					$imgInfo['height'] = $h;
					$spriteHeight += $h;
				}
			}

			$imgList[$k] = $imgInfo;
		}


		// Ошибка определения размера холста
		if (!$imgList || !$spriteWidth || !$spriteHeight) return;

		// Проверка существования директории для сохранения
		if ( !JFolder::exists($this->outPath) ) {
			JFolder::create($this->outPath);
			$fp = fopen($this->outPath.'/index.html', 'w');
			fclose($fp);
		}
		
		$name = md5(serialize($list).'|'.$width.'|'.$height);

		// Создание холста для спрайта
		$spriteHdl = imagecreatetruecolor($spriteWidth, $spriteHeight);
		imagealphablending($spriteHdl, false);
		imagesavealpha($spriteHdl, false);
					
		$sprite = new JImageJsf($spriteHdl);
		$imgObj = new JImageJsf();

		// Добавление в спрайт изображений и генерация css
        $fp = fopen($this->outPath.'/'.$name.'.css', 'w');
        fwrite($fp, '.c_'.$name.' {background-image:url('.$name.'.jpg);overflow:hidden;}'."\n");
        $currHeight = 0;
        
        foreach ($imgList as $k => &$img)
        {
			// css
			fwrite($fp, '.c_'.$name.'_'.$k.' {background-position: -0px -'.$currHeight.'px;width:'.$img['width'].'px;height:'.$img['height'].'px;}'."\n");

			$imgObj->loadFile( $img['file'] );
			$imgObj->resize($img['width'], $img['height'], false, JImage::SCALE_INSIDE);
			$imgObj->copyTo($sprite, 0, $currHeight);

			

			$currHeight += $img['height'];
		}

		fclose($fp);

		// Сохранение спрайта
        $sprite->toFile($this->outPath.'/'.$name.'.jpg', IMAGETYPE_JPEG);

        return $name;
	}

}
