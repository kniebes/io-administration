<?php declare(strict_types=1);

namespace App\Enum;

enum ImageLicense: string
{
    case AllRightsReserved = 'allRightsReserved';

    case Cc0 = 'cc0';

    case CcBy = 'cc-by';

    case CcBySa = 'cc-by-sa';

    case CcByNc = 'cc-by-nc';

    case CcByNcSa = 'cc-by-nc-sa';

    case CcByNd = 'cc-by-nd';

    case PublicDomain = 'publicDomain';
}
