<?php
/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author  Niels Theen <ntheen@databay.de>
 */
class ObjectHelper
{
	public function lookUpType($objectId)
	{
		return ilObject::_lookupType($objectId);
	}
}
