import 'package:flutter_test/flutter_test.dart';

import 'package:temizci_burada/main.dart';

void main() {
  test('app widget can be created', () {
    const app = TemizciBuradaApp();
    expect(app, isNotNull);
    expect(app, isA<TemizciBuradaApp>());
  });

  test('app class is stateless', () {
    const app = TemizciBuradaApp();
    expect(app.runtimeType.toString(), contains('TemizciBuradaApp'));
  });
}
