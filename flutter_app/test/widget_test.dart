import 'package:flutter_test/flutter_test.dart';
import 'package:ekota_somiti/app.dart';

void main() {
  testWidgets('App launches', (WidgetTester tester) async {
    await tester.pumpWidget(const EkotaApp());
    await tester.pumpAndSettle();
    expect(find.text('Ekota Somiti'), findsOneWidget);
  });
}
