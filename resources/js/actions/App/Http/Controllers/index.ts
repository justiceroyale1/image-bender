import DashboardController from './DashboardController'
import NewImageGenerationPageController from './NewImageGenerationPageController'
import ImageGenerationController from './ImageGenerationController'
import UsageController from './UsageController'
import CreditController from './CreditController'
import Webhooks from './Webhooks'
import Settings from './Settings'

const Controllers = {
    DashboardController: Object.assign(DashboardController, DashboardController),
    NewImageGenerationPageController: Object.assign(NewImageGenerationPageController, NewImageGenerationPageController),
    ImageGenerationController: Object.assign(ImageGenerationController, ImageGenerationController),
    UsageController: Object.assign(UsageController, UsageController),
    CreditController: Object.assign(CreditController, CreditController),
    Webhooks: Object.assign(Webhooks, Webhooks),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers