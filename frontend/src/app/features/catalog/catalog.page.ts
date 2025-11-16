import { ChangeDetectionStrategy, Component } from '@angular/core';

@Component({
  standalone: true,
  selector: 'app-catalog',
  templateUrl: './catalog.page.html',
  changeDetection: ChangeDetectionStrategy.OnPush
})
export class CatalogPage {
  /** External catalog URL so it's easy to change later */
  readonly externalUrl = 'https://businessfirstacademy.net/catalog';
}
